<?php

namespace Tests\Feature\Admissions;

use App\Modules\Admissions\Actions\InitiatePaymentAction;
use App\Modules\Admissions\Actions\ProcessPaymentCallbackAction;
use App\Modules\Admissions\Enums\PaymentStatus;
use App\Modules\Admissions\Enums\PaymentType;
use App\Modules\Admissions\Models\AdmissionPayment;
use App\Modules\Admissions\Models\PaymentGatewayLog;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Admissions\Support\BuildsAdmissions;
use Tests\TestCase;

class PaymentSecurityTest extends TestCase
{
    use DatabaseTransactions, BuildsAdmissions;

    private function submitted(): \App\Modules\Admissions\Models\Application
    {
        return $this->makeApplication(['status' => 'PENDING_APP_FEE']);
    }

    public function test_amount_comes_from_the_programme_not_the_request(): void
    {
        $application = $this->submitted();

        $result = app(InitiatePaymentAction::class)->execute($application, PaymentType::APPLICATION_FEE, 'mock');

        $this->assertEquals('500.00', number_format((float) $result['payment']->amount, 2, '.', ''));
    }

    public function test_callback_without_a_valid_signature_is_rejected_and_logged(): void
    {
        $application = $this->submitted();
        $init = app(InitiatePaymentAction::class)->execute($application, PaymentType::APPLICATION_FEE, 'mock');

        try {
            app(ProcessPaymentCallbackAction::class)->execute($init['payment'], [
                'transaction_ref' => $init['payment']->transaction_ref, 'status' => 'SUCCESS', 'amount' => '500.00', 'currency' => 'BDT',
            ]);
            $this->fail('Unsigned callback must be rejected');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('signature', $e->errors());
        }

        $this->assertEquals('PENDING_APP_FEE', $application->fresh()->status, 'Status must not change');
        $this->assertEquals(PaymentStatus::INITIATED, $init['payment']->fresh()->payment_status);
        $this->assertTrue(PaymentGatewayLog::where('payment_id', $init['payment']->id)->where('signature_valid', false)->exists(), 'Rejected callback is logged');
        $this->assertTrue(\App\Modules\Admissions\Models\AuditLog::where('record_id', $init['payment']->id)->where('action', 'payment_callback_rejected')->exists());
    }

    public function test_signed_callback_with_wrong_amount_is_rejected(): void
    {
        $application = $this->submitted();
        $init = app(InitiatePaymentAction::class)->execute($application, PaymentType::APPLICATION_FEE, 'mock');

        // The amount is part of the signature, so a valid signature for 1.00 still cannot pay 500.00.
        $this->expectException(ValidationException::class);
        app(ProcessPaymentCallbackAction::class)->execute($init['payment'], $this->signedCallback($init['payment'], 'SUCCESS', '1.00'));
    }

    public function test_replayed_success_is_processed_once(): void
    {
        $application = $this->submitted();
        $payment = $this->payFee($application, PaymentType::APPLICATION_FEE);
        $this->assertEquals('SUBMITTED', $application->fresh()->status);

        // Replay the exact same signed callback.
        app(ProcessPaymentCallbackAction::class)->execute($payment, $this->signedCallback($payment));

        $this->assertEquals(1, $application->statusHistory()->where('to_status', 'SUBMITTED')->count());
        $this->assertEquals(1, AdmissionPayment::where('application_id', $application->id)->where('payment_status', 'SUCCESS')->count());
    }

    public function test_failed_payment_leaves_status_unchanged_and_allows_retry(): void
    {
        $application = $this->submitted();
        $first = app(InitiatePaymentAction::class)->execute($application, PaymentType::APPLICATION_FEE, 'mock')['payment'];

        app(ProcessPaymentCallbackAction::class)->execute($first, $this->signedCallback($first, 'FAILED'));
        $this->assertEquals('FAILED', $first->fresh()->payment_status->value);
        $this->assertEquals('PENDING_APP_FEE', $application->fresh()->status);

        $retry = app(InitiatePaymentAction::class)->execute($application, PaymentType::APPLICATION_FEE, 'mock')['payment'];
        app(ProcessPaymentCallbackAction::class)->execute($retry, $this->signedCallback($retry));

        $this->assertEquals('SUBMITTED', $application->fresh()->status);
    }

    public function test_cancelled_payment_leaves_status_unchanged(): void
    {
        $application = $this->submitted();
        $payment = app(InitiatePaymentAction::class)->execute($application, PaymentType::APPLICATION_FEE, 'mock')['payment'];

        app(ProcessPaymentCallbackAction::class)->execute($payment, $this->signedCallback($payment, 'CANCELLED'));

        $this->assertEquals('CANCELLED', $payment->fresh()->payment_status->value);
        $this->assertEquals('PENDING_APP_FEE', $application->fresh()->status);
    }

    public function test_unknown_gateway_is_refused_not_silently_replaced(): void
    {
        $application = $this->submitted();

        $this->expectException(ValidationException::class);
        app(InitiatePaymentAction::class)->execute($application, PaymentType::APPLICATION_FEE, 'nonsense');
    }

    public function test_second_successful_application_fee_is_refused(): void
    {
        $application = $this->submitted();
        $this->payFee($application, PaymentType::APPLICATION_FEE);

        $this->expectException(ValidationException::class);
        app(InitiatePaymentAction::class)->execute($application->fresh(), PaymentType::APPLICATION_FEE, 'mock');
    }

    public function test_the_mock_gateway_is_refused_when_it_is_disabled(): void
    {
        // A live server must never let a 'mock' payment mark a fee as paid.
        config(['admissions.mock_payments_enabled' => false]);
        $application = $this->submitted();

        $this->expectException(ValidationException::class);
        app(InitiatePaymentAction::class)->execute($application, PaymentType::APPLICATION_FEE, 'mock');
    }

    public function test_sslcommerz_refuses_to_run_without_credentials(): void
    {
        $application = $this->submitted();

        // Credentials are empty in the test environment, so the request must fail cleanly, not reach the internet.
        $this->expectException(ValidationException::class);
        app(InitiatePaymentAction::class)->execute($application, PaymentType::APPLICATION_FEE, 'sslcommerz');
    }
}
