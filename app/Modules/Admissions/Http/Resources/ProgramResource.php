<?php

namespace App\Modules\Admissions\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProgramResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'title' => $this->title,
            'section_type' => $this->section_type?->value,
            'academic_year' => $this->academicYear ? [
                'id' => $this->academicYear->id,
                'name' => $this->academicYear->name,
            ] : null,
            'academic_class' => $this->academicClass ? [
                'id' => $this->academicClass->id,
                'code' => $this->academicClass->code,
                'name' => $this->academicClass->name,
            ] : null,
            'academic_group' => $this->academicGroup ? [
                'id' => $this->academicGroup->id,
                'code' => $this->academicGroup->code,
                'name' => $this->academicGroup->name,
            ] : null,
            'application_fee' => (float) $this->application_fee,
            'admission_fee' => (float) $this->admission_fee,
            'capacity' => (int) $this->capacity,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'min_eligibility' => $this->min_eligibility ?? [],
            'is_open' => $this->isOpen(),
        ];
    }
}
