<?php

namespace App\Modules\Admissions\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordScoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'application_id' => 'sometimes|string',
            'obtained_marks' => 'required|numeric|min:0|max:100',
            'comments' => 'nullable|string|max:1000',
        ];
    }
}
