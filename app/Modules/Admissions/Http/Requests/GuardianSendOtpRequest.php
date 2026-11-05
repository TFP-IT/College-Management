<?php

namespace App\Modules\Admissions\Http\Requests;

use App\Modules\Admissions\Rules\BdMobile;
use Illuminate\Foundation\Http\FormRequest;

class GuardianSendOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'program_id' => 'required|uuid|exists:admission_programs,id',
            'guardian_name' => 'required|string|min:3|max:255',
            'relation' => 'required|string|in:FATHER,MOTHER,GUARDIAN',
            'email' => 'required|email|max:255',
            'phone' => ['required', new BdMobile],
        ];
    }
}
