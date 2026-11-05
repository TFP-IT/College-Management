<?php

namespace App\Modules\Admissions\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentGuardian extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'student_guardians';

    protected $fillable = [
        'student_id',
        'guardian_id',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class, 'student_id');
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(GuardianProfile::class, 'guardian_id');
    }
}
