<?php

namespace App\Modules\Admissions\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicGroup extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'academic_groups';

    protected $fillable = [
        'class_id',
        'code',
        'name',
    ];

    public function academicClass(): BelongsTo
    {
        return $this->belongsTo(AcademicClass::class, 'class_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(AcademicSection::class, 'group_id');
    }

    public function admissionPrograms(): HasMany
    {
        return $this->hasMany(AdmissionProgram::class, 'group_id');
    }
}
