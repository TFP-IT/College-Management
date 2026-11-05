<?php

namespace App\Modules\Admissions\Http\Requests;

use App\Modules\Admissions\Rules\BdMobile;
use Illuminate\Foundation\Http\FormRequest;

class TrackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'application_no' => 'required|string|max:50',
            'phone' => ['required', new BdMobile],
            'code' => 'required|digits:6',
        ];
    }
}
