<?php

namespace App\Modules\Admissions\Http\Requests;

use App\Modules\Admissions\Rules\BdMobile;
use Illuminate\Foundation\Http\FormRequest;

class UpdateApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'candidate_name' => 'sometimes|required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => ['sometimes', 'required', new BdMobile],
            'candidate_dob' => 'nullable|date|before:today',
            'gender' => 'nullable|string|in:MALE,FEMALE,OTHER',
            'blood_group' => 'nullable|string|max:10',
            'current_address' => 'nullable|string|max:500',
            'permanent_address' => 'nullable|string|max:500',
            'previous_school' => 'nullable|string|max:255',
            'previous_gpa' => 'nullable|numeric|between:0,5',
            'academic_history' => 'nullable|array',
            'guardians' => 'nullable|array|max:5',
            'guardians.*.relation' => 'required|string|in:FATHER,MOTHER,GUARDIAN',
            'guardians.*.full_name' => 'required|string|max:255',
            'guardians.*.phone' => ['required', new BdMobile],
            'guardians.*.email' => 'nullable|email|max:255',
            'guardians.*.occupation' => 'nullable|string|max:100',
            'guardians.*.nid_passport' => 'nullable|string|max:100',
            'guardians.*.is_primary' => 'nullable|boolean',
        ];
    }
}
