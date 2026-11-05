<?php

namespace App\Modules\Admissions\Payments;

use App\Modules\Admissions\Enums\PaymentStatus;
use App\Modules\Admissions\Models\AdmissionPayment;
use App\Modules\Admissions\Services\PaymentSignature;
use Illuminate\Support\Str;

/**
 * Development gateway. Its callbacks are signed with HMAC (see PaymentSignature).
 * A callback without a valid signature is rejected, so nobody can fake a payment by posting data.
 */
class MockGateway implements PaymentGateway
{
    public function getName(): string
    {
        return 'mock';
    }

    public function initiatePayment(
        AdmissionPayment $payment,
        array $customerData,
        string $successUrl,
        string $cancelUrl,
        string $failUrl
    ): array {
        $redirect = route('admissions.payments.mock.checkout', ['payment' => $payment->id]);

        return [
            'redirect_url' => $redirect,
            'session_id' => 'MOCK_SES_' . Str::random(16),
            'provider_data' => ['provider' => 'mock', 'created_at' => now()->toIso8601String()],
        ];
    }

    public function verifyPayment(array $callbackData): array
    {
        $ref = (string) ($callbackData['transaction_ref'] ?? $callbackData['tran_id'] ?? '');
        $status = strtoupper((string) ($callbackData['status'] ?? 'FAILED'));
        $amount = (string) ($callbackData['amount'] ?? '');

        $valid = PaymentSignature::verify($ref, $status, $amount, $callbackData['signature'] ?? null);

        $mapped = match ($status) {
            'SUCCESS' => PaymentStatus::SUCCESS,
            'CANCELLED' => PaymentStatus::CANCELLED,
            default => PaymentStatus::FAILED,
        };

        return [
            'success' => $valid && $mapped === PaymentStatus::SUCCESS,
            'signature_valid' => $valid,
            'status' => $mapped,
            'transaction_ref' => $ref,
            'provider_trx_id' => $callbackData['provider_trx_id'] ?? null,
            'amount' => (float) $amount,
            'currency' => $callbackData['currency'] ?? 'BDT',
            'raw' => array_diff_key($callbackData, ['signature' => true]),
        ];
    }
}
