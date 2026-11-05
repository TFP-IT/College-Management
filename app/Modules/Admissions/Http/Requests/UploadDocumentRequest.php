<?php

namespace App\Modules\Admissions\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'doc_type' => 'required|string|in:BIRTH_CERT,PREVIOUS_TRANSCRIPT,PHOTO,NID,OTHER',
            // Size is checked in DocumentService (5 MB, photo 1 MB); the MIME is checked by content.
            'file' => 'required|file|max:5120',
        ];
    }
}
