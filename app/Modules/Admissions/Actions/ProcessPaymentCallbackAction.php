<?php

namespace App\Modules\Admissions\Actions;

use App\Modules\Admissions\Enums\ApplicationStatus;
use App\Modules\Admissions\Enums\PaymentStatus;
use App\Modules\Admissions\Enums\PaymentType;
use App\Modules\Admissions\Events\PaymentProcessed;
use App\Modules\Admissions\Models\AdmissionPayment;
use App\Modules\Admissions\Models\PaymentGatewayLog;
use App\Modules\Admissions\Payments\GatewayManager;
use App\Modules\Admissions\Services\ApplicationStateMachine;
use App\Modules\Admissions\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Order of checks for every callback:
 *   1. gateway verifies the callback (signature or server-to-server call)   [outside transaction]
 *   2. the raw callback is logged, valid or not                             [outside transaction, so rejections persist]
 *   3. signature must be valid, amount and currency must match the stored payment
 *   4. inside a transaction: lock the payment, re-check, then change status  [repeats of a success are no-ops]
 */
class ProcessPaymentCallbackAction
{
    public function __construct(
        protected GatewayManager $gatewayManager,
        protected ApplicationStateMachine $stateMachine,
        protected AuditService $audit
    ) {}

    public function execute(AdmissionPayment $payment, array $callbackData): AdmissionPayment
    {
        $gateway = $this->gatewayManager->get($payment->provider);
        $verification = $gateway->verifyPayment($callbackData);

        PaymentGatewayLog::create([
            'payment_id' => $payment->id,
            'provider' => $payment->provider,
            'direction' => 'CALLBACK',
            'payload' => array_diff_key($callbackData, ['signature' => true, 'store_passwd' => true]),
            'signature_valid' => (bool) $verification['signature_valid'],
            'http_status' => 200,
            'created_at' => now(),
        ]);

        if (!$verification['signature_valid']) {
            $this->audit->record('payment_callback_rejected', $payment->id, [], ['reason' => 'invalid_signature']);
            throw ValidationException::withMessages(['signature' => 'The payment callback could not be verified.']);
        }

        $amountOk = abs((float) $verification['amount'] - (float) $payment->amount) < 0.01;
        $currencyOk = ($verification['currency'] ?? 'BDT') === $payment->currency;
        if (!$amountOk || !$currencyOk) {
            $this->audit->record('payment_callback_rejected', $payment->id, [], ['reason' => 'amount_or_currency_mismatch']);
            throw ValidationException::withMessages(['amount' => 'The payment amount does not match this request.']);
        }

        return DB::transaction(function () use ($payment, $verification) {
            $locked = AdmissionPayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($locked->payment_status === PaymentStatus::SUCCESS) {
                return $locked; // duplicate or replayed callback: nothing to do
            }

            $newStatus = $verification['status'];

            if ($newStatus !== PaymentStatus::SUCCESS) {
                $locked->update([
                    'payment_status' => $newStatus->value,
                    'payment_details' => array_merge($locked->payment_details ?? [], ['last_callback' => $newStatus->value]),
                ]);
                $this->audit->record('payment_' . strtolower($newStatus->value), $locked->id, [], ['status' => $newStatus->value]);

                return $locked->fresh();
            }

            $duplicate = AdmissionPayment::where('application_id', $locked->application_id)
                ->where('payment_type', $locked->payment_type->value)
                ->where('payment_status', PaymentStatus::SUCCESS->value)
                ->exists();
            if ($duplicate) {
                throw ValidationException::withMessages(['payment' => 'This fee has already been paid.']);
            }

            $locked->update([
                'payment_status' => PaymentStatus::SUCCESS->value,
                'paid_at' => now(),
                'payment_details' => array_merge($locked->payment_details ?? [], [
                    'provider_trx_id' => $verification['provider_trx_id'],
                ]),
            ]);

            $application = $locked->application()->lockForUpdate()->firstOrFail();

            if ($locked->payment_type === PaymentType::APPLICATION_FEE && $application->application_status === ApplicationStatus::PENDING_APP_FEE) {
                $this->stateMachine->transition($application, ApplicationStatus::SUBMITTED, null, "Application fee paid. Trx: {$locked->transaction_ref}");
            } elseif ($locked->payment_type === PaymentType::ADMISSION_FEE && $application->application_status === ApplicationStatus::APPROVED) {
                $this->stateMachine->transition($application, ApplicationStatus::ADMISSION_FEE_PAID, null, "Admission fee paid. Trx: {$locked->transaction_ref}");
            } else {
                // Paid after the application moved on (e.g. withdrawn): keep the money and flag it for staff.
                $locked->update(['payment_details' => array_merge($locked->payment_details ?? [], ['needs_review' => true])]);
            }

            $this->audit->record('payment_confirmed', $locked->id, [], ['type' => $locked->payment_type->value, 'amount' => (float) $locked->amount]);

            event(new PaymentProcessed($application, $locked));

            return $locked->fresh();
        });
    }
}
