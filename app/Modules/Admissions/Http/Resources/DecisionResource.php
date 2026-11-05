<?php

namespace App\Modules\Admissions\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DecisionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'decision' => $this->decision?->value,
            'remarks' => $this->remarks,
            'decided_at' => $this->decided_at?->toIso8601String(),
            'decided_by' => $this->decider ? [
                'id' => $this->decider->id,
                'name' => $this->decider->name,
            ] : null,
        ];
    }
}
