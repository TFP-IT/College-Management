<?php

namespace App\Modules\Admissions\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssessmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'assessment_type' => $this->assessment_type?->value,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'venue' => $this->venue,
            'total_marks' => (float) $this->total_marks,
            'obtained_marks' => $this->obtained_marks !== null ? (float) $this->obtained_marks : null,
            'comments' => $this->comments,
            'evaluated_at' => $this->evaluated_at?->toIso8601String(),
            'evaluator' => $this->evaluator ? [
                'id' => $this->evaluator->id,
                'name' => $this->evaluator->name,
            ] : null,
        ];
    }
}
