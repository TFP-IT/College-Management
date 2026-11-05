<?php

namespace App\Modules\Admissions\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * A guardian's portal account, identified by a verified email address.
 * Like applicant accounts, it has an unguessable password: sign-in is by email code only.
 */
class GuardianAccountService
{
    public function forEmail(string $email, string $name, string $phone): User
    {
        $user = User::query()
            ->where('email', $email)
            ->where('user_type', 'temp')
            ->whereNull('role_id')
            ->first();

        if ($user) {
            $user->forceFill(['name' => $name, 'phone_number' => $phone])->save();

            return $user;
        }

        return User::create([
            'name' => $name,
            'email' => $email,
            'phone_number' => $phone,
            'password' => Hash::make(Str::random(48)),
            'user_type' => 'temp',
            'status' => 1,
            'school_id' => 1,
        ]);
    }
}
