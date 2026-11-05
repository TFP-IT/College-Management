<?php

namespace App\Modules\Admissions\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'academic_years';

    protected $fillable = [
        'name',
        'starts_on',
        'ends_on',
        'is_current',
    ];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'is_current' => 'boolean',
    ];

    public function sections(): HasMany
    {
        return $this->hasMany(AcademicSection::class, 'academic_year_id');
    }

    public function admissionPrograms(): HasMany
    {
        return $this->hasMany(AdmissionProgram::class, 'academic_year_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class, 'academic_year_id');
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }
}
