<?php

namespace App\Modules\Admissions\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicSection extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'academic_sections';

    protected $fillable = [
        'academic_year_id',
        'class_id',
        'group_id',
        'name',
        'capacity',
    ];

    protected $casts = [
        'capacity' => 'integer',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function academicClass(): BelongsTo
    {
        return $this->belongsTo(AcademicClass::class, 'class_id');
    }

    public function academicGroup(): BelongsTo
    {
        return $this->belongsTo(AcademicGroup::class, 'group_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class, 'section_id');
    }

    public function availableSeatsCount(): int
    {
        $enrolled = $this->enrollments()->where('status', 'ACTIVE')->count();
        return max(0, $this->capacity - $enrolled);
    }
}
