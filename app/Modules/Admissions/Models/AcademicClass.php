<?php

namespace App\Modules\Admissions\Models;

use App\Modules\Admissions\Enums\SectionType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicClass extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'academic_classes';

    protected $fillable = [
        'code',
        'name',
        'section_type',
        'sort_order',
    ];

    protected $casts = [
        'section_type' => SectionType::class,
        'sort_order' => 'integer',
    ];

    public function groups(): HasMany
    {
        return $this->hasMany(AcademicGroup::class, 'class_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(AcademicSection::class, 'class_id');
    }

    public function admissionPrograms(): HasMany
    {
        return $this->hasMany(AdmissionProgram::class, 'class_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class, 'class_id');
    }

    public function sectionTypeValue(): ?string
    {
        return $this->section_type ? (string) $this->attributes['section_type'] : null;
    }
}
