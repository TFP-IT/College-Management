<?php

namespace App\Services\Payments;

use App\Models\Admission;
use App\Models\PaymentTransaction;
use App\Models\StudentFeeManager;
use App\Models\User;
use App\Modules\Campus\Services\FinanceService;
use App\Services\WebAccountSync;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The only place a transaction becomes paid. The browser redirect and the server-to-server notice both
 * come here, and neither is trusted: the gateway is asked again, and its answer must name this same
 * transaction, amount and currency.
 */
class PaymentSettlement
{
    public const PAID = 'paid';
    public const HELD = 'hold';
    public const REJECTED = 'rejected';
    public const UNREACHABLE = 'unreachable';

    public function __construct(private SslCommerzClient $gateway)
    {
    }

    public function settle(string $tranId, string $valId): string
    {
        $transaction = PaymentTransaction::where('transaction_id', $tranId)->first();
        if (!$transaction) {
            return self::REJECTED;
        }
        if ($transaction->status === 'paid') {
            return self::PAID;
        }

        try {
            $result = $this->gateway->validatePayment($valId);
        } catch (ConnectionException $e) {
            Log::warning('SSLCommerz validation timed out', ['tran_id' => $tranId]);

            return self::UNREACHABLE;
        }

        if (!in_array($result['status'] ?? '', ['VALID', 'VALIDATED'], true)) {
            return self::REJECTED;
        }

        if (!$this->matches($transaction, $result)) {
            Log::warning('SSLCommerz validation does not match the transaction', [
                'tran_id' => $tranId,
                'expected_amount' => (string) $transaction->amount,
                'gateway_tran_id' => $result['tran_id'] ?? null,
                'gateway_amount' => $result['amount'] ?? null,
                'gateway_currency' => $result['currency_type'] ?? null,
            ]);

            return self::REJECTED;
        }

        return DB::transaction(function () use ($transaction, $result) {
            $locked = PaymentTransaction::whereKey($transaction->getKey())->lockForUpdate()->first();
            if ($locked->status === 'paid') {
                return self::PAID;
            }

            if ((string) ($result['risk_level'] ?? '0') === '1') {
                $locked->status = 'hold';
                $locked->gateway_response = $result;
                $locked->save();
                Log::warning('SSLCommerz payment held for review', ['tran_id' => $locked->transaction_id]);

                return self::HELD;
            }

            $locked->markPaid(null, $result, $this->method($result));
            if ($locked->payable_type === StudentFeeManager::class) {
                // Money was really received, so it is recorded even if the invoice was settled meanwhile.
                app(FinanceService::class)->record((int) $locked->payable_id, (int) round((float) $locked->amount), 'online', $locked->transaction_id, null, false);
            } else {
                $this->applyToAdmission($locked);
            }

            return self::PAID;
        });
    }

    private function matches(PaymentTransaction $transaction, array $result): bool
    {
        $amount = $result['amount'] ?? null;
        $currency = strtoupper((string) ($result['currency_type'] ?? $transaction->currency));

        return hash_equals((string) $transaction->transaction_id, (string) ($result['tran_id'] ?? ''))
            && is_numeric($amount)
            && abs((float) $amount - (float) $transaction->amount) < 0.01
            && $currency === strtoupper((string) $transaction->currency);
    }

    private function method(array $result): string
    {
        $cardType = strtolower($result['card_type'] ?? '');
        foreach (['bkash', 'nagad', 'rocket'] as $wallet) {
            if (str_contains($cardType, $wallet)) {
                return $wallet;
            }
        }

        return 'card';
    }

    private function applyToAdmission(PaymentTransaction $transaction): void
    {
        $admission = $transaction->payable_type === Admission::class ? Admission::find($transaction->payable_id) : null;
        if (!$admission) {
            return;
        }

        if ($transaction->type === 'application_fee' && $admission->status === 'pending') {
            $admission->status = 'under_review';
            $admission->save();
        }

        if ($transaction->type === 'admission_fee') {
            $student = User::find($admission->user_id);
            if ($student) {
                app(WebAccountSync::class)->enrollAfterAdmissionFee($student);
            }
        }
    }
}
