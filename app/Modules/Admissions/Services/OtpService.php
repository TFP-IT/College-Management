<?php

namespace App\Modules\Admissions\Services;

use App\Modules\Admissions\Models\OtpCode;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use RuntimeException;

class OtpService
{
    public const PURPOSES = ['APPLICATION_AUTH', 'TRACK', 'GUARDIAN_AUTH'];

    /** Purposes the phone-based API accepts. GUARDIAN_AUTH is email-based and used only by the web guardian sign-in. */
    public const API_PURPOSES = ['APPLICATION_AUTH', 'TRACK'];

    protected const EXPIRY_MINUTES = 10;
    protected const MAX_ATTEMPTS = 5;
    protected const MAX_REQUESTS_PER_WINDOW = 5;
    protected const RATE_LIMIT_WINDOW_MINUTES = 15;

    public function generateOtp(string $mobile, string $purpose = 'APPLICATION_AUTH'): string
    {
        $this->assertPurpose($purpose);

        $recent = OtpCode::query()
            ->where('mobile', $mobile)
            ->where('purpose', $purpose)
            ->where('created_at', '>=', now()->subMinutes(self::RATE_LIMIT_WINDOW_MINUTES))
            ->count();

        if ($recent >= self::MAX_REQUESTS_PER_WINDOW) {
            throw new RuntimeException('Too many codes requested. Please wait a few minutes and try again.');
        }

        // Invalidate earlier pending codes by marking them consumed (rows are kept so the rate limit still counts).
        OtpCode::query()
            ->where('mobile', $mobile)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        $code = (string) random_int(100000, 999999);

        OtpCode::create([
            'mobile' => $mobile,
            'code_hash' => Hash::make($code),
            'purpose' => $purpose,
            'attempts' => 0,
            'expires_at' => now()->addMinutes(self::EXPIRY_MINUTES),
        ]);

        return $code;
    }

    /**
     * @throws InvalidArgumentException with a user-facing message when the code is wrong or unusable.
     */
    public function verifyOtp(string $mobile, string $code, string $purpose = 'APPLICATION_AUTH'): bool
    {
        $this->assertPurpose($purpose);

        // Development/testing only: the fixed test code is accepted without a real message.
        if (config('admissions.test_otp_enabled') && $code === (string) config('admissions.test_otp_code')) {
            OtpCode::query()->where('mobile', $mobile)->where('purpose', $purpose)->whereNull('consumed_at')
                ->update(['consumed_at' => now()]);

            return true;
        }

        $otp = OtpCode::query()
            ->where('mobile', $mobile)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->latest('created_at')
            ->first();

        if (!$otp || $otp->isExpired()) {
            throw new InvalidArgumentException('This code is invalid or has expired. Please request a new one.');
        }

        if ($otp->attempts >= self::MAX_ATTEMPTS) {
            throw new InvalidArgumentException('Maximum verification attempts exceeded. Please request a new code.');
        }

        $otp->increment('attempts');

        if (!Hash::check($code, $otp->code_hash)) {
            $remaining = max(0, self::MAX_ATTEMPTS - $otp->attempts);
            throw new InvalidArgumentException("Invalid verification code. {$remaining} attempts remaining.");
        }

        $otp->update(['consumed_at' => now()]);

        return true;
    }

    private function assertPurpose(string $purpose): void
    {
        if (!in_array($purpose, self::PURPOSES, true)) {
            throw new InvalidArgumentException('Unsupported verification purpose.');
        }
    }
}
