<?php

namespace App\Modules\Admissions\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EnrollApplicantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'application_id' => 'sometimes|string',
            'section_id' => 'required|uuid|exists:academic_sections,id',
            'roll_no' => 'nullable|string|max:20',
        ];
    }
}
