<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'users';

    protected $fillable = [
        'uuid',
        'name', 'email', 'password', 'role_id', 'parent_id', 'school_id', 'code', 'user_information', 'student_info', 'documents', 'status', 'department_id', 'designation', 'language', 'school_role', 'account_status', 'student_id', 'teacher_id', 'phone_number', 'user_type'
    ];

    protected static function booted(): void
    {
        static::creating(function ($user) {
            if (empty($user->uuid)) {
                $user->uuid = (string) Str::uuid();
            }
        });
    }

    public function studentProfile()
    {
        return $this->hasOne(\App\Modules\Admissions\Models\StudentProfile::class, 'user_id');
    }

    public function guardianProfile()
    {
        return $this->hasOne(\App\Modules\Admissions\Models\GuardianProfile::class, 'user_id');
    }

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function checkEnrollment(){
        return $this->hasMany(Enrollment::class,'user_id');
    }


     public function getTomoNameAttribute()
    {
        return $this->checkEnrollment()->class_id;  
    }
}
