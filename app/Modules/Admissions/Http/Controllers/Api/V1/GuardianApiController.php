<?php

namespace App\Modules\Admissions\Http\Controllers\Api\V1;

use App\Mail\GuardianOtpMail;
use App\Modules\Admissions\Http\Requests\GuardianSendOtpRequest;
use App\Modules\Admissions\Http\Requests\GuardianVerifyOtpRequest;
use App\Modules\Admissions\Models\AdmissionProgram;
use App\Modules\Admissions\Services\GuardianAccountService;
use App\Modules\Admissions\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use RuntimeException;

/**
 * The guardian's sign-in for the app, the same steps as the website:
 * 1. guardian/otp/request: details + programme, a 6-digit code is emailed.
 * 2. guardian/otp/verify: the code returns a token with the "applicant" and "guardian" abilities.
 * 3. POST /apply with that token: the guardian is recorded as the primary guardian.
 */
class GuardianApiController extends BaseApiController
{
    private const PENDING_MINUTES = 10;

    public function requestOtp(GuardianSendOtpRequest $request, OtpService $otp): JsonResponse
    {
        $program = AdmissionProgram::where('is_active', true)->findOrFail($request->program_id);
        if (!$program->isOpen()) {
            return $this->respondWithError('This programme is closed or past its deadline.', null, 422);
        }

        $email = strtolower(trim($request->email));

        try {
            $code = $otp->generateOtp($email, 'GUARDIAN_AUTH');
        } catch (RuntimeException $e) {
            return $this->respondWithError($e->getMessage(), null, 429);
        }

        $debug = app()->environment('local') && config('admissions.otp_expose_debug_code');

        try {
            Mail::to($email)->send(new GuardianOtpMail($code, $request->guardian_name));
        } catch (\Throwable $e) {
            report($e);
            if (!$debug) {
                return $this->respondWithError('We could not send the code to your email. Please try again later.', null, 503);
            }
        }

        Cache::put($this->pendingKey($email), [
            'program_id' => $program->id,
            'guardian_name' => $request->guardian_name,
            'relation' => $request->relation,
            'phone' => $request->phone,
        ], now()->addMinutes(self::PENDING_MINUTES));

        $data = ['sent' => true, 'email' => $email, 'expires_in_minutes' => self::PENDING_MINUTES];
        if ($debug) {
            $data['debug_otp'] = $code;
        }

        return $this->respondWithSuccess($data, 'Verification code sent to your email.');
    }

    public function verifyOtp(GuardianVerifyOtpRequest $request, OtpService $otp, GuardianAccountService $accounts): JsonResponse
    {
        $email = strtolower(trim($request->email));
        $pending = Cache::get($this->pendingKey($email));

        if (!$pending) {
            return $this->respondWithError('No pending code for this email. Request a new code.', ['email' => ['Request a new code first.']], 422);
        }

        try {
            $otp->verifyOtp($email, $request->code, 'GUARDIAN_AUTH');
        } catch (InvalidArgumentException $e) {
            return $this->respondWithError($e->getMessage(), ['code' => [$e->getMessage()]], 422);
        }

        return $this->guard(function () use ($email, $pending, $accounts) {
            $guardian = $accounts->forEmail($email, $pending['guardian_name'], $pending['phone']);
            $minutes = (int) config('admissions.applicant_token_minutes', 1440);
            $token = $guardian->createToken('guardian', ['applicant', 'guardian'], now()->addMinutes($minutes))->plainTextToken;

            Cache::forget($this->pendingKey($email));

            return $this->respondWithSuccess([
                'verified' => true,
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_in_minutes' => $minutes,
                'program_id' => $pending['program_id'],
                'guardian' => [
                    'name' => $guardian->name,
                    'email' => $guardian->email,
                    'phone' => $guardian->phone_number,
                    'relation' => $pending['relation'],
                ],
            ], 'Verification successful. Use this token to apply.');
        });
    }

    private function pendingKey(string $email): string
    {
        return 'admissions:guardian_pending:' . sha1($email);
    }
}
