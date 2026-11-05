<?php

namespace App\Modules\Admissions\Http\Requests;

use App\Modules\Admissions\Rules\BdMobile;
use Illuminate\Foundation\Http\FormRequest;

class ApplyProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ownership and the verified phone number are checked in the controller
    }

    public function rules(): array
    {
        return [
            'program_id' => 'required|uuid|exists:admission_programs,id',
            'candidate_name' => 'required|string|max:255',
            'phone' => ['required', new BdMobile],
            'email' => 'nullable|email|max:255',
            'candidate_dob' => 'required|date|before:today',
            'gender' => 'required|string|in:MALE,FEMALE,OTHER',
            'blood_group' => 'nullable|string|max:10',
            'current_address' => 'required|string|max:500',
            'permanent_address' => 'nullable|string|max:500',
            'previous_school' => 'nullable|string|max:255',
            'previous_gpa' => 'nullable|numeric|between:0,5',
            'academic_history' => 'nullable|array',
            // Only used when a guardian's token applies: the guardian's relation to the candidate.
            'guardian_relation' => 'nullable|string|in:FATHER,MOTHER,GUARDIAN',
        ];
    }
}
