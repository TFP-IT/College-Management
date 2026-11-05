<?php

namespace App\Modules\Admissions\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Finds or creates the applicant's user account from their verified mobile number.
 * The account gets an unguessable password; the applicant signs in with OTP, not a password.
 */
class ApplicantAccountService
{
    public function forMobile(string $mobile, ?string $name = null): User
    {
        $user = User::query()
            ->where('phone_number', $mobile)
            ->where('user_type', 'temp')
            ->whereNull('role_id')
            ->first();

        if ($user) {
            return $user;
        }

        return User::create([
            'name' => $name ?: 'Applicant ' . substr($mobile, -4),
            'phone_number' => $mobile,
            'password' => Hash::make(Str::random(48)),
            'user_type' => 'temp',
            'status' => 1,
            'school_id' => 1,
        ]);
    }
}
