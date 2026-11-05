<?php

namespace App\Modules\Admissions\Payments;

use App\Modules\Admissions\Enums\PaymentStatus;
use App\Modules\Admissions\Models\AdmissionPayment;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * SSLCommerz sandbox/live integration. A callback is only trusted after SSLCommerz's
 * validation API confirms the val_id for the same store, transaction and amount.
 */
class SSLCommerzGateway implements PaymentGateway
{
    protected string $storeId;
    protected string $storePassword;
    protected bool $isSandbox;
    protected string $baseUrl;

    public function __construct()
    {
        $this->storeId = (string) config('services.sslcommerz.store_id');
        $this->storePassword = (string) config('services.sslcommerz.store_password');
        $this->isSandbox = filter_var(config('services.sslcommerz.sandbox'), FILTER_VALIDATE_BOOLEAN);
        $this->baseUrl = $this->isSandbox ? 'https://sandbox.sslcommerz.com' : 'https://securepay.sslcommerz.com';
    }

    public function getName(): string
    {
        return 'sslcommerz';
    }

    public function isConfigured(): bool
    {
        return $this->storeId !== '' && $this->storePassword !== '';
    }

    public function initiatePayment(
        AdmissionPayment $payment,
        array $customerData,
        string $successUrl,
        string $cancelUrl,
        string $failUrl
    ): array {
        if (!$this->isConfigured()) {
            throw new RuntimeException('SSLCommerz credentials are not configured.');
        }

        $response = Http::asForm()->timeout(20)->post("{$this->baseUrl}/gwprocess/v4/api.php", [
            'store_id' => $this->storeId,
            'store_passwd' => $this->storePassword,
            'total_amount' => $payment->amount,
            'currency' => $payment->currency ?? 'BDT',
            'tran_id' => $payment->transaction_ref,
            'success_url' => $successUrl,
            'fail_url' => $failUrl,
            'cancel_url' => $cancelUrl,
            'ipn_url' => route('admissions.payments.ipn'),
            'cus_name' => $customerData['name'] ?? 'Candidate',
            'cus_email' => $customerData['email'] ?? 'applicant@mcc.edu.bd',
            'cus_add1' => $customerData['address'] ?? 'Dhaka, Bangladesh',
            'cus_city' => 'Dhaka',
            'cus_country' => 'Bangladesh',
            'cus_phone' => $customerData['phone'] ?? '01700000000',
            'shipping_method' => 'NO',
            'num_of_item' => 1,
            'product_name' => 'Admission Fee',
            'product_category' => 'Education',
            'product_profile' => 'non-physical-goods',
        ]);

        if (!$response->successful()) {
            throw new RuntimeException('SSLCommerz is unreachable (HTTP ' . $response->status() . ').');
        }

        $data = $response->json();
        if (($data['status'] ?? '') !== 'SUCCESS' || empty($data['GatewayPageURL'])) {
            throw new RuntimeException('SSLCommerz rejected the request: ' . ($data['failedreason'] ?? 'unknown reason'));
        }

        return [
            'redirect_url' => $data['GatewayPageURL'],
            'session_id' => $data['sessionkey'] ?? null,
            'provider_data' => ['sessionkey' => $data['sessionkey'] ?? null],
        ];
    }

    public function verifyPayment(array $callbackData): array
    {
        $valId = $callbackData['val_id'] ?? null;
        $tranId = (string) ($callbackData['tran_id'] ?? '');
        $amount = (float) ($callbackData['amount'] ?? 0);

        $base = [
            'success' => false,
            'signature_valid' => false,
            'status' => PaymentStatus::FAILED,
            'transaction_ref' => $tranId,
            'provider_trx_id' => null,
            'amount' => $amount,
            'currency' => $callbackData['currency'] ?? null,
            'raw' => $callbackData,
        ];

        if (!$this->isConfigured() || empty($valId) || $tranId === '') {
            $base['status'] = strtoupper((string) ($callbackData['status'] ?? '')) === 'CANCELLED'
                ? PaymentStatus::CANCELLED
                : PaymentStatus::FAILED;

            return $base;
        }

        $response = Http::timeout(20)->get("{$this->baseUrl}/validator/api/validationserverAPI.php", [
            'val_id' => $valId,
            'store_id' => $this->storeId,
            'store_passwd' => $this->storePassword,
            'format' => 'json',
        ]);

        $data = $response->successful() ? (array) $response->json() : [];
        $statusStr = strtoupper((string) ($data['status'] ?? ''));
        $confirmed = in_array($statusStr, ['VALID', 'VALIDATED'], true)
            && ($data['tran_id'] ?? null) === $tranId
            && abs((float) ($data['amount'] ?? -1) - $amount) < 0.01;

        return array_merge($base, [
            'success' => $confirmed,
            'signature_valid' => $confirmed,
            'status' => $confirmed ? PaymentStatus::SUCCESS : PaymentStatus::FAILED,
            'provider_trx_id' => $data['bank_tran_id'] ?? $valId,
            'amount' => (float) ($data['amount'] ?? $amount),
            'currency' => $data['currency_type'] ?? $base['currency'],
            'raw' => $data,
        ]);
    }
}
