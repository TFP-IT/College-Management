<?php

namespace App\Modules\Admissions\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentProfile extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'student_profiles';

    protected $fillable = [
        'user_id',
        'user_uuid',
        'student_number',
        'blood_group',
        'dob',
        'gender',
        'nationality',
        'current_address',
    ];

    protected $casts = [
        'dob' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(GuardianProfile::class, 'student_guardians', 'student_id', 'guardian_id')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class, 'student_id');
    }

    public function currentEnrollment()
    {
        return $this->hasOne(StudentEnrollment::class, 'student_id')->where('status', 'ACTIVE')->latestOfMany();
    }
}
