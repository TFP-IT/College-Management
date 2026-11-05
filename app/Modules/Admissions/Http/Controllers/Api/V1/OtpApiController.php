<?php

namespace App\Modules\Admissions\Http\Controllers\Api\V1;

use App\Modules\Admissions\Http\Requests\SendOtpRequest;
use App\Modules\Admissions\Http\Requests\VerifyOtpRequest;
use App\Modules\Admissions\Services\ApplicantAccountService;
use App\Modules\Admissions\Services\OtpService;
use Illuminate\Http\JsonResponse;

class OtpApiController extends BaseApiController
{
    public function send(SendOtpRequest $request, OtpService $otp): JsonResponse
    {
        try {
            $code = $otp->generateOtp($request->phone, $request->purpose ?? 'APPLICATION_AUTH');
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage(), null, 429);
        }

        return $this->guard(function () use ($code) {

            $data = ['sent' => true];
            // Only for local development. Never enabled in production.
            if (config('admissions.otp_expose_debug_code') && app()->environment('local', 'testing')) {
                $data['debug_otp'] = $code;
            }

            return $this->respondWithSuccess($data, 'Verification code sent.');
        });
    }

    public function verify(VerifyOtpRequest $request, OtpService $otp, ApplicantAccountService $accounts): JsonResponse
    {
        $purpose = $request->purpose ?? 'APPLICATION_AUTH';

        return $this->guard(function () use ($request, $otp, $accounts, $purpose) {
            $otp->verifyOtp($request->phone, $request->code, $purpose);

            if ($purpose !== 'APPLICATION_AUTH') {
                return $this->respondWithSuccess(['verified' => true], 'Verification successful.');
            }

            $user = $accounts->forMobile($request->phone);
            $minutes = (int) config('admissions.applicant_token_minutes', 1440);
            $token = $user->createToken('applicant', ['applicant'], now()->addMinutes($minutes))->plainTextToken;

            return $this->respondWithSuccess([
                'verified' => true,
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_in_minutes' => $minutes,
            ], 'Verification successful.');
        });
    }
}
