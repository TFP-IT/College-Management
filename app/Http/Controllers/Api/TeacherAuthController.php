<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\TeacherSetupOtpMail;
use App\Models\User;
use App\Modules\Admissions\Models\OtpCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Teacher first-time setup and password recovery via email OTP.
 * Teachers are pre-registered in the DB (role_id = 3, status = 1, password = null).
 */
class TeacherAuthController extends Controller
{
    private const PURPOSE = 'TEACHER_SETUP';
    private const OTP_TTL_MINUTES = 10;
    private const MAX_OTP_ATTEMPTS = 5;
    private const SETUP_TOKEN_TTL_MINUTES = 15;
    private const GENERIC_SENT_MESSAGE = 'If this email is registered, an OTP has been sent.';

    public function requestSetupOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $email = Str::lower(trim($request->email));

        $limits = [
            ['key' => 'teacher-otp:email-minute:' . $email, 'max' => 1, 'decay' => 60],
            ['key' => 'teacher-otp:email-hour:' . $email, 'max' => 5, 'decay' => 3600],
            // Teachers often share the college Wi-Fi, so the per-IP cap is looser than per-email.
            ['key' => 'teacher-otp:ip-hour:' . $request->ip(), 'max' => 20, 'decay' => 3600],
        ];

        foreach ($limits as $limit) {
            if (RateLimiter::tooManyAttempts($limit['key'], $limit['max'])) {
                $seconds = RateLimiter::availableIn($limit['key']);
                return response()->json([
                    'status' => 'error',
                    'message' => $seconds <= 60
                        ? "Please wait {$seconds} seconds before requesting another code."
                        : 'Too many code requests. Please try again in ' . ceil($seconds / 60) . ' minutes.',
                    'retry_after' => $seconds,
                ], 429);
            }
        }

        foreach ($limits as $limit) {
            RateLimiter::hit($limit['key'], $limit['decay']);
        }

        $user = $this->findActiveTeacher($email);
        $recipient = $user?->email;
        $name = $user?->name;

        if (!$user && $this->openSignupEnabled() && !User::where('email', $email)->exists()) {
            $recipient = $email;
            $name = 'Teacher';
        }

        if ($recipient) {
            $otp = (string) random_int(100000, 999999);

            OtpCode::where('mobile', $email)->where('purpose', self::PURPOSE)->delete();

            OtpCode::create([
                'mobile' => $email,
                'purpose' => self::PURPOSE,
                'code_hash' => Hash::make($otp),
                'attempts' => 0,
                'expires_at' => now()->addMinutes(self::OTP_TTL_MINUTES),
            ]);

            // Sending after the response keeps timing identical for registered and unknown emails.
            dispatch(function () use ($recipient, $name, $otp) {
                try {
                    Mail::to($recipient)->send(new TeacherSetupOtpMail($otp, $name));
                } catch (\Throwable $e) {
                    Log::error('Teacher setup OTP email failed: ' . $e->getMessage(), ['email' => $recipient]);
                }
            })->afterResponse();
        }

        return response()->json([
            'status' => 'success',
            'message' => self::GENERIC_SENT_MESSAGE,
        ], 200);
    }

    public function verifySetupOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
            'otp' => 'required|digits:6',
        ]);

        $ipKey = 'teacher-otp-verify:ip:' . $request->ip();
        if (RateLimiter::tooManyAttempts($ipKey, 30)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Too many verification attempts. Please try again later.',
                'retry_after' => RateLimiter::availableIn($ipKey),
            ], 429);
        }
        RateLimiter::hit($ipKey, 900);

        $email = Str::lower(trim($request->email));
        $invalid = response()->json([
            'status' => 'error',
            'message' => 'Verification code is invalid or has expired. Please request a new code.',
        ], 400);

        $otpRecord = OtpCode::where('mobile', $email)
            ->where('purpose', self::PURPOSE)
            ->whereNull('consumed_at')
            ->latest()
            ->first();

        if (!$otpRecord) {
            return $invalid;
        }

        if ($otpRecord->isExpired() || $otpRecord->attempts >= self::MAX_OTP_ATTEMPTS) {
            $otpRecord->delete();
            return $invalid;
        }

        if (!Hash::check($request->otp, $otpRecord->code_hash)) {
            $otpRecord->increment('attempts');
            $remaining = self::MAX_OTP_ATTEMPTS - $otpRecord->attempts;

            if ($remaining <= 0) {
                $otpRecord->delete();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Too many invalid attempts. Please request a new code.',
                ], 429);
            }

            return response()->json([
                'status' => 'error',
                'message' => "Invalid verification code. {$remaining} attempt(s) remaining.",
            ], 400);
        }

        $otpRecord->delete();

        $user = $this->findActiveTeacher($email);
        if (!$user && $this->openSignupEnabled() && !User::where('email', $email)->exists()) {
            $user = User::create([
                'name' => Str::headline(Str::before($email, '@')),
                'email' => $email,
                'role_id' => 3,
                'school_id' => config('teacher_auth.open_signup_school_id'),
                'status' => 1,
                'password' => null,
            ]);
        }
        if (!$user) {
            return $invalid;
        }

        $setupToken = Str::random(64);
        Cache::put($this->setupTokenKey($setupToken), $user->id, now()->addMinutes(self::SETUP_TOKEN_TTL_MINUTES));

        return response()->json([
            'status' => 'success',
            'message' => 'Verification successful.',
            'setup_token' => $setupToken,
            'expires_in' => self::SETUP_TOKEN_TTL_MINUTES * 60,
            'teacher' => [
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'needs_username' => empty($user->username),
            ],
        ], 200);
    }

    public function completeSetup(Request $request)
    {
        $request->merge([
            'username' => $request->filled('username') ? Str::lower(trim($request->username)) : null,
        ]);

        $request->validate([
            'setup_token' => 'required|string|size:64',
            'password' => 'required|string|min:8|max:72|confirmed',
            'username' => ['nullable', 'string', 'min:3', 'max:30', 'regex:/^[a-z0-9_]+$/'],
        ], [
            'username.regex' => 'Username may only contain lowercase letters, numbers and underscores.',
        ]);

        $tokenKey = $this->setupTokenKey($request->setup_token);
        $userId = Cache::get($tokenKey);
        $user = $userId ? User::where('id', $userId)->where('status', 1)->first() : null;

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Setup session has expired or is invalid. Please start over.',
            ], 401);
        }

        if (empty($user->username)) {
            if (!$request->username) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Please choose a username.',
                    'errors' => ['username' => ['Please choose a username.']],
                ], 422);
            }

            // The teacher login matches username, teacher ID, email and phone, so a username must not collide with any of them.
            $taken = User::where('id', '!=', $user->id)
                ->where(function ($q) use ($request) {
                    $q->where('username', $request->username)
                      ->orWhere('teacher_id', $request->username)
                      ->orWhere('email', $request->username)
                      ->orWhere('phone_number', $request->username);
                })
                ->exists();

            if ($taken) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This username is already taken. Please choose another.',
                    'errors' => ['username' => ['This username is already taken.']],
                ], 422);
            }

            $user->username = $request->username;
        }

        if (!Cache::pull($tokenKey)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Setup session has expired or is invalid. Please start over.',
            ], 401);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        $user->tokens()->delete();
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Password set successfully. Welcome to Maestro Crown College.',
            'token' => $token,
            'user' => $user,
            'role' => 'teacher',
        ], 200);
    }

    private function findActiveTeacher(string $email): ?User
    {
        return User::where('email', $email)
            ->where('status', 1)
            ->where(function ($q) {
                $q->where('role_id', 3)->orWhere('user_type', 'teacher');
            })
            ->first();
    }

    private function openSignupEnabled(): bool
    {
        return (bool) config('teacher_auth.open_signup') && !app()->isProduction();
    }

    private function setupTokenKey(string $token): string
    {
        return 'teacher_setup:' . hash('sha256', $token);
    }
}
