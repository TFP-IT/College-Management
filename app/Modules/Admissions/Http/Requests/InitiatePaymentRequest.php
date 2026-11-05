<?php

namespace App\Modules\Admissions\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InitiatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_type' => 'required|string|in:APPLICATION_FEE,ADMISSION_FEE',
            'provider' => 'nullable|string|max:50',
            // 'amount' is deliberately not accepted: the amount always comes from the programme.
        ];
    }
}
