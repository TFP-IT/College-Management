<?php

namespace App\Modules\Admissions\Services;

use App\Modules\Admissions\Models\Application;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;
use InvalidArgumentException;

/**
 * Signed admission pass. Issued only when the council approves a candidate.
 * Token = base64url("{appId}|{appNo}|{expiryTs}|{hmac}"), HMAC keyed with APP_KEY.
 */
class QrTokenService
{
    public function generateToken(Application $application, ?Carbon $expiresAt = null): string
    {
        $expiresAt = $expiresAt ?? now()->addDays(30);
        $payload = $application->id . '|' . $application->application_no . '|' . $expiresAt->timestamp;
        $signature = hash_hmac('sha256', $payload, $this->key());

        $token = rtrim(strtr(base64_encode($payload . '|' . $signature), '+/', '-_'), '=');

        $application->forceFill([
            'qr_token' => $token,
            'qr_token_hash' => hash('sha256', $token),
            'qr_expires_at' => $expiresAt,
        ])->save();

        return $token;
    }

    /**
     * @return array{application_id:string, application_number:string, expires_at:Carbon}
     * @throws InvalidArgumentException when the token is malformed, tampered with or expired.
     */
    public function verifyToken(string $token): array
    {
        $b64 = strtr($token, '-_', '+/');
        $decoded = base64_decode(str_pad($b64, strlen($b64) + (4 - strlen($b64) % 4) % 4, '='), true);

        if ($decoded === false) {
            throw new InvalidArgumentException('Malformed QR token.');
        }

        $parts = explode('|', $decoded);
        if (count($parts) !== 4) {
            throw new InvalidArgumentException('Invalid QR token.');
        }

        [$appId, $appNumber, $timestamp, $signature] = $parts;
        $expected = hash_hmac('sha256', "{$appId}|{$appNumber}|{$timestamp}", $this->key());

        if (!hash_equals($expected, $signature)) {
            throw new InvalidArgumentException('QR token signature is invalid.');
        }

        $expiresAt = Carbon::createFromTimestamp((int) $timestamp);
        if ($expiresAt->isPast()) {
            throw new InvalidArgumentException('QR token has expired.');
        }

        return [
            'application_id' => $appId,
            'application_number' => $appNumber,
            'expires_at' => $expiresAt,
        ];
    }

    public function getVerificationUrl(Application $application): ?string
    {
        return $application->qr_token ? URL::route('admissions.qr.verify', ['token' => $application->qr_token]) : null;
    }

    private function key(): string
    {
        return (string) config('app.key');
    }
}
