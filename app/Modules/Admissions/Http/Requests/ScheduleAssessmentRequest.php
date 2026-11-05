<?php

namespace App\Modules\Admissions\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScheduleAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'application_id' => 'sometimes|string',
            'assessment_type' => 'required|string|in:WRITTEN,VIVA,INTERVIEW',
            'scheduled_at' => 'required|date|after:now',
            'venue' => 'nullable|string|max:255',
        ];
    }
}
