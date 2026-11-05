<?php

namespace App\Modules\Admissions\Http\Requests;

use App\Modules\Admissions\Rules\BdMobile;
use App\Modules\Admissions\Services\OtpService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', new BdMobile],
            'purpose' => ['nullable', Rule::in(OtpService::PURPOSES)],
        ];
    }
}
