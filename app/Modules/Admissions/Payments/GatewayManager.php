<?php

namespace App\Modules\Admissions\Payments;

use InvalidArgumentException;

class GatewayManager
{
    /** @var array<string, PaymentGateway> */
    protected array $gateways = [];

    public function __construct()
    {
        if (config('admissions.mock_payments_enabled')) {
            $this->register('mock', new MockGateway());
        }
        $this->register('sslcommerz', new SSLCommerzGateway());
    }

    public function register(string $name, PaymentGateway $gateway): self
    {
        $this->gateways[strtolower($name)] = $gateway;

        return $this;
    }

    /** Resolves a gateway by name. Unknown names are refused, never silently replaced. */
    public function get(?string $name = null): PaymentGateway
    {
        $provider = strtolower($name ?: config('admissions.default_payment_provider', 'mock'));

        if (!isset($this->gateways[$provider])) {
            throw new InvalidArgumentException("Unknown payment provider: {$provider}");
        }

        return $this->gateways[$provider];
    }

    public function has(string $name): bool
    {
        return isset($this->gateways[strtolower($name)]);
    }

    public function availableGateways(): array
    {
        return array_keys($this->gateways);
    }
}
