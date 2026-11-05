<?php

namespace App\Modules\Admissions\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class GuardianProfile extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'guardian_profiles';

    protected $fillable = [
        'user_id',
        'user_uuid',
        'relation',
        'occupation',
        'nid_passport',
        'emergency_contact',
    ];

    protected $casts = [
        'nid_passport' => 'encrypted',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(StudentProfile::class, 'student_guardians', 'guardian_id', 'student_id')
            ->withPivot('is_primary')
            ->withTimestamps();
    }
}
