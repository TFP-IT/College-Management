<?php

namespace Tests\Feature\Security;

use App\Models\Admission;
use App\Models\PaymentTransaction;
use Illuminate\Http\Client\Request as GatewayRequest;
use Illuminate\Support\Facades\Http;

class PaymentIntegrityTest extends SecurityTestCase
{
    private array $validation = [];

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.sslcommerz.store_id' => 'test-store',
            'services.sslcommerz.store_password' => 'test-password',
            'services.sslcommerz.sandbox' => true,
            'fees.application_fee' => 500,
            'fees.admission_fee' => 3000,
        ]);

        Http::fake([
            '*gwprocess*' => Http::response(['status' => 'SUCCESS', 'GatewayPageURL' => 'https://gateway.test/pay']),
            '*validationserverAPI*' => fn () => Http::response($this->validation),
        ]);
    }

    private function pendingPayment(): array
    {
        $guardian = $this->user(6);
        $student = $this->user(7, ['parent_id' => $guardian->id]);
        $admission = $this->admission($student, $guardian);

        $this->withToken($guardian->createToken('t')->plainTextToken)
            ->postJson('/api/payment/initiate', ['application_no' => $admission->application_no])
            ->assertOk();

        return [$admission, PaymentTransaction::where('application_no', $admission->application_no)->firstOrFail(), $guardian];
    }

    private function gatewaySays(PaymentTransaction $payment, array $override = []): void
    {
        $this->validation = $override + [
            'status' => 'VALID', 'tran_id' => $payment->transaction_id, 'amount' => '500.00',
            'currency_type' => 'BDT', 'risk_level' => '0', 'card_type' => 'BKASH-BKash',
        ];
    }

    public function test_the_amount_sent_by_the_client_is_ignored(): void
    {
        $guardian = $this->user(6);
        $admission = $this->admission($this->user(7, ['parent_id' => $guardian->id]), $guardian);

        $this->withToken($guardian->createToken('t')->plainTextToken)
            ->postJson('/api/payment/initiate', ['application_no' => $admission->application_no, 'amount' => 10, 'fee_type' => 'admission_fee'])
            ->assertOk();

        $this->assertEquals(3000, PaymentTransaction::where('application_no', $admission->application_no)->value('amount'));
        Http::assertSent(fn (GatewayRequest $r) => str_contains($r->url(), 'gwprocess') && (float) $r['total_amount'] === 3000.0);
    }

    public function test_another_account_cannot_start_a_payment_for_this_application(): void
    {
        $admission = $this->admission($this->user(7), $this->user(6));

        $this->withToken($this->user(6)->createToken('t')->plainTextToken)
            ->postJson('/api/payment/initiate', ['application_no' => $admission->application_no])
            ->assertNotFound();

        Http::assertNothingSent();
    }

    public function test_a_verified_payment_marks_the_transaction_paid_once(): void
    {
        [$admission, $payment] = $this->pendingPayment();
        $this->gatewaySays($payment);

        $this->post('/api/payment/ipn', ['tran_id' => $payment->transaction_id, 'val_id' => 'v1'])->assertOk()->assertSee('OK');
        $paidAt = $payment->fresh()->paid_at;
        $this->post('/api/payment/success', ['tran_id' => $payment->transaction_id, 'val_id' => 'v1'])->assertRedirectContains('status=success');

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('bkash', $payment->fresh()->method);
        $this->assertEquals($paidAt, $payment->fresh()->paid_at);
        $this->assertSame('under_review', Admission::find($admission->id)->status);
    }

    /** @return array<string, array{array}> */
    public static function mismatches(): array
    {
        return [
            'another transaction' => [['tran_id' => 'MCC_ADM_somebody_else']],
            'smaller amount' => [['amount' => '10.00']],
            'another currency' => [['currency_type' => 'USD']],
            'missing transaction id' => [['tran_id' => null]],
            'not valid' => [['status' => 'INVALID_TRANSACTION']],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('mismatches')]
    public function test_a_gateway_answer_that_does_not_match_pays_nothing(array $override): void
    {
        [$admission, $payment] = $this->pendingPayment();
        $this->gatewaySays($payment, $override);

        $this->post('/api/payment/success', ['tran_id' => $payment->transaction_id, 'val_id' => 'v1'])->assertRedirectContains('status=fail');
        $this->post('/api/payment/ipn', ['tran_id' => $payment->transaction_id, 'val_id' => 'v1']);

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertSame('pending', Admission::find($admission->id)->status);
    }

    public function test_a_risky_payment_is_held(): void
    {
        [, $payment] = $this->pendingPayment();
        $this->gatewaySays($payment, ['risk_level' => '1']);

        $this->post('/api/payment/ipn', ['tran_id' => $payment->transaction_id, 'val_id' => 'v1'])->assertSee('HOLD');

        $this->assertSame('hold', $payment->fresh()->status);
    }

    public function test_a_paid_fee_cannot_be_charged_again_and_stays_paid_in_the_summary(): void
    {
        [$admission, $payment, $guardian] = $this->pendingPayment();
        $this->gatewaySays($payment);
        $this->post('/api/payment/ipn', ['tran_id' => $payment->transaction_id, 'val_id' => 'v1']);

        $this->withToken($guardian->createToken('again')->plainTextToken)
            ->postJson('/api/payment/initiate', ['application_no' => $admission->application_no])
            ->assertStatus(409);

        $this->assertSame(['status' => 'paid', 'amount' => 500.0], $admission->fresh()->feeSummary('application_fee'));
    }

    public function test_cancel_notice_cannot_undo_a_paid_transaction(): void
    {
        [, $payment] = $this->pendingPayment();
        $this->gatewaySays($payment);
        $this->post('/api/payment/ipn', ['tran_id' => $payment->transaction_id, 'val_id' => 'v1']);

        $this->post('/api/payment/cancel', ['tran_id' => $payment->transaction_id]);

        $this->assertSame('paid', $payment->fresh()->status);
    }

    public function test_payment_is_refused_when_credentials_are_not_configured(): void
    {
        config(['services.sslcommerz.store_id' => '', 'services.sslcommerz.store_password' => '']);
        $guardian = $this->user(6);
        $admission = $this->admission($this->user(7, ['parent_id' => $guardian->id]), $guardian);

        $this->withToken($guardian->createToken('t')->plainTextToken)
            ->postJson('/api/payment/initiate', ['application_no' => $admission->application_no])
            ->assertStatus(503);

        $this->assertSame(0, PaymentTransaction::where('application_no', $admission->application_no)->count());
    }
}
