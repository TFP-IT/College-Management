<?php

namespace App\Modules\Admissions\Services;

/**
 * HMAC signature for the mock gateway's server-to-server callbacks.
 * The signed string covers the transaction reference, status, and amount so none can be changed.
 */
class PaymentSignature
{
    public static function sign(string $transactionRef, string $status, string $amount): string
    {
        return hash_hmac('sha256', implode('|', [$transactionRef, strtoupper($status), $amount]), (string) config('admissions.mock_secret'));
    }

    public static function verify(string $transactionRef, string $status, string $amount, ?string $signature): bool
    {
        if (empty($signature)) {
            return false;
        }

        return hash_equals(self::sign($transactionRef, $status, $amount), $signature);
    }
}
