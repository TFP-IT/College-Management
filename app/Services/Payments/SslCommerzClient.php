<?php

namespace App\Services\Payments;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/** Thin HTTP client for the SSLCommerz session and validation APIs. */
class SslCommerzClient
{
    public function isConfigured(): bool
    {
        return $this->storeId() !== '' && $this->storePassword() !== '';
    }

    public function isSandbox(): bool
    {
        return filter_var(config('services.sslcommerz.sandbox', true), FILTER_VALIDATE_BOOLEAN);
    }

    /** @throws ConnectionException */
    public function createSession(array $fields)
    {
        $fields = ['store_id' => $this->storeId(), 'store_passwd' => $this->storePassword()] + $fields;

        return $this->send(fn (PendingRequest $http) => $http->asForm()->post($this->baseUrl() . '/gwprocess/v4/api.php', $fields));
    }

    /** @throws ConnectionException */
    public function validatePayment(string $valId): array
    {
        $url = $this->baseUrl() . '/validator/api/validationserverAPI.php?' . http_build_query([
            'val_id' => $valId,
            'store_id' => $this->storeId(),
            'store_passwd' => $this->storePassword(),
            'v' => 1,
            'format' => 'json',
        ]);

        // The sandbox stalls when "@" in the store password is percent-encoded.
        $url = str_replace(
            'store_passwd=' . urlencode($this->storePassword()),
            'store_passwd=' . $this->storePassword(),
            $url
        );

        $result = $this->send(fn (PendingRequest $http) => $http->get($url))->json();

        return is_array($result) ? $result : [];
    }

    private function storeId(): string
    {
        return (string) config('services.sslcommerz.store_id');
    }

    private function storePassword(): string
    {
        return (string) config('services.sslcommerz.store_password');
    }

    private function baseUrl(): string
    {
        return $this->isSandbox() ? 'https://sandbox.sslcommerz.com' : 'https://securepay.sslcommerz.com';
    }

    /** Sandbox handshakes often stall; retry before giving up. */
    private function send(callable $send)
    {
        $last = null;
        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                return $send(Http::withOptions(['curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]])
                    ->connectTimeout(25)
                    ->timeout(40));
            } catch (ConnectionException $e) {
                $last = $e;
                usleep(400000);
            }
        }

        throw $last;
    }
}
