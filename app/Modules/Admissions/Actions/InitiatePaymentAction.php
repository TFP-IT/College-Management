<?php

namespace App\Modules\Admissions\Actions;

use App\Modules\Admissions\Enums\ApplicationStatus;
use App\Modules\Admissions\Enums\PaymentStatus;
use App\Modules\Admissions\Enums\PaymentType;
use App\Modules\Admissions\Models\AdmissionPayment;
use App\Modules\Admissions\Models\Application;
use App\Modules\Admissions\Models\PaymentGatewayLog;
use App\Modules\Admissions\Payments\GatewayManager;
use App\Modules\Admissions\Services\AuditService;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;

class InitiatePaymentAction
{
    public function __construct(
        protected GatewayManager $gatewayManager,
        protected AuditService $audit
    ) {}

    /**
     * The amount always comes from the programme. The client cannot choose it.
     *
     * @return array{payment:AdmissionPayment, redirect_url:string, session_id:?string}
     */
    public function execute(Application $application, PaymentType $type, ?string $provider = null): array
    {
        $program = $application->program;
        $amount = $type === PaymentType::APPLICATION_FEE ? (float) $program->application_fee : (float) $program->admission_fee;

        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'No online payment is required for this step.']);
        }

        $status = $application->application_status;
        if ($type === PaymentType::APPLICATION_FEE && $status !== ApplicationStatus::PENDING_APP_FEE) {
            throw ValidationException::withMessages(['status' => 'The application fee can only be paid after you submit the form.']);
        }
        if ($type === PaymentType::ADMISSION_FEE) {
            if ($status !== ApplicationStatus::APPROVED) {
                throw ValidationException::withMessages(['status' => 'The admission fee can only be paid after council approval.']);
            }
            if (!$application->qrIsValid()) {
                throw ValidationException::withMessages(['qr' => 'The admission pass has expired. Contact the admissions office.']);
            }
        }

        $alreadyPaid = $application->payments()
            ->where('payment_type', $type->value)
            ->where('payment_status', PaymentStatus::SUCCESS->value)
            ->exists();
        if ($alreadyPaid) {
            throw ValidationException::withMessages(['payment' => 'This fee has already been paid.']);
        }

        $providerName = strtolower($provider ?: config('admissions.default_payment_provider', 'mock'));
        if (!$this->gatewayManager->has($providerName)) {
            throw ValidationException::withMessages(['provider' => 'Unknown payment provider.']);
        }
        $gateway = $this->gatewayManager->get($providerName);

        $payment = AdmissionPayment::create([
            'application_id' => $application->id,
            'payment_type' => $type->value,
            'amount' => $amount,
            'currency' => 'BDT',
            'provider' => $providerName,
            'transaction_ref' => 'TRX_' . now()->format('YmdHis') . '_' . strtoupper(Str::random(8)),
            'payment_status' => PaymentStatus::INITIATED->value,
            'payment_details' => [],
        ]);

        $customer = [
            'name' => $application->applicant_name,
            'email' => $application->email ?? 'applicant@mcc.edu.bd',
            'phone' => $application->mobile,
            'address' => $application->current_address ?? 'Dhaka, Bangladesh',
        ];

        try {
            $response = $gateway->initiatePayment(
                $payment,
                $customer,
                route('admissions.payments.callback', ['payment' => $payment->id, 'status' => 'success']),
                route('admissions.payments.callback', ['payment' => $payment->id, 'status' => 'cancelled']),
                route('admissions.payments.callback', ['payment' => $payment->id, 'status' => 'fail'])
            );
        } catch (RuntimeException $e) {
            $payment->update(['payment_status' => PaymentStatus::FAILED->value, 'payment_details' => ['error' => $e->getMessage()]]);
            throw ValidationException::withMessages(['provider' => 'The payment gateway is unavailable right now. Please try again shortly.']);
        }

        $payment->update(['payment_details' => $response['provider_data'] ?? []]);

        PaymentGatewayLog::create([
            'payment_id' => $payment->id,
            'provider' => $providerName,
            'direction' => 'REQUEST',
            'payload' => ['transaction_ref' => $payment->transaction_ref, 'amount' => $amount, 'type' => $type->value],
            'signature_valid' => true,
            'created_at' => now(),
        ]);

        $this->audit->record('payment_initiated', $payment->id, [], ['type' => $type->value, 'amount' => $amount, 'provider' => $providerName], null);

        return [
            'payment' => $payment->fresh(),
            'redirect_url' => $response['redirect_url'],
            'session_id' => $response['session_id'] ?? null,
        ];
    }
}
