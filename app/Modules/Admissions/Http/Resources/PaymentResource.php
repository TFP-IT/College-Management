<?php

namespace App\Modules\Admissions\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_type' => $this->payment_type?->value,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'provider' => $this->provider,
            'transaction_ref' => $this->transaction_ref,
            'payment_status' => $this->payment_status?->value,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
