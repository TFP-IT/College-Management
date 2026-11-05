<?php

namespace App\Modules\Admissions\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentEnrollment extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'student_enrollments';

    protected $fillable = [
        'student_id',
        'academic_year_id',
        'class_id',
        'group_id',
        'section_id',
        'roll_no',
        'status',
        'enrolled_on',
    ];

    protected $casts = [
        'enrolled_on' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->enrolled_on)) {
                $model->enrolled_on = now()->toDateString();
            }
        });
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class, 'student_id');
    }

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

    public function academicSection(): BelongsTo
    {
        return $this->belongsTo(AcademicSection::class, 'section_id');
    }
}
