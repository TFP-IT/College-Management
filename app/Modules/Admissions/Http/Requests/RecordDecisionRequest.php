<?php

namespace App\Modules\Admissions\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'application_id' => 'sometimes|string',
            'decision' => 'required|string|in:APPROVED,WAITLISTED,REJECTED',
            'remarks' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:1000',
        ];
    }
}
