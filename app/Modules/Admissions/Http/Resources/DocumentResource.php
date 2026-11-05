<?php

namespace App\Modules\Admissions\Http\Resources;

use App\Modules\Admissions\Services\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $docService = app(DocumentService::class);

        return [
            'id' => $this->id,
            'doc_type' => $this->doc_type?->value,
            'original_filename' => $this->original_filename,
            'file_size_bytes' => $this->file_size_bytes,
            'mime_type' => $this->mime_type,
            'doc_status' => $this->doc_status?->value,
            'review_notes' => $this->review_notes,
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'secure_view_url' => $docService->getSecureUrl($this->resource),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
