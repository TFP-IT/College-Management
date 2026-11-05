<?php

namespace App\Modules\Admissions\Http\Controllers\Api\V1;

use App\Modules\Admissions\Http\Requests\TrackRequest;
use App\Modules\Admissions\Http\Resources\ApplicationResource;
use App\Modules\Admissions\Models\Application;
use App\Modules\Admissions\Services\OtpService;
use Illuminate\Http\JsonResponse;

class TrackApiController extends BaseApiController
{
    /** Generic error on every failure, so the endpoint cannot be used to check whether a number exists. */
    public function track(TrackRequest $request, OtpService $otp): JsonResponse
    {
        $generic = fn () => $this->respondWithError('We could not find an application matching these details.', null, 404);

        try {
            $otp->verifyOtp($request->phone, $request->code, 'TRACK');
        } catch (\Throwable) {
            return $generic();
        }

        $application = Application::with(['program', 'documents', 'payments', 'statusHistory'])
            ->where('application_no', $request->application_no)
            ->where('mobile', $request->phone)
            ->first();

        if (!$application) {
            return $generic();
        }

        return $this->respondWithSuccess(new ApplicationResource($application), 'Application status retrieved.');
    }
}
