<?php

namespace App\Modules\Admissions\Payments;

use App\Modules\Admissions\Models\AdmissionPayment;

interface PaymentGateway
{
    public function getName(): string;

    /**
     * @return array{redirect_url:string, session_id:?string, provider_data:array}
     */
    public function initiatePayment(
        AdmissionPayment $payment,
        array $customerData,
        string $successUrl,
        string $cancelUrl,
        string $failUrl
    ): array;

    /**
     * Confirms a callback. Must check the gateway's signature or call the gateway server-to-server.
     * @return array{success:bool, signature_valid:bool, status:\App\Modules\Admissions\Enums\PaymentStatus, transaction_ref:string, provider_trx_id:?string, amount:float, currency:?string, raw:array}
     */
    public function verifyPayment(array $callbackData): array;
}
