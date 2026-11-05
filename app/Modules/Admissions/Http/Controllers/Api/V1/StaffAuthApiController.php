<?php

namespace App\Modules\Admissions\Http\Controllers\Api\V1;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/** Staff sign-in for the council and admissions desk. Applicants never receive a staff token. */
class StaffAuthApiController extends BaseApiController
{
    private const STAFF_PERMISSIONS = [
        'admission.application.view', 'admission.decide', 'admission.document.verify',
        'admission.assessment.record', 'admission.enroll', 'admission.report.view',
    ];

    public function login(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email', 'password' => 'required|string']);

        $user = User::where('email', $request->email)->where('status', 1)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->respondWithError('Invalid email or password.', null, 401);
        }

        if (!$user->hasAnyPermission(self::STAFF_PERMISSIONS)) {
            return $this->respondWithError('This account does not have admissions access.', null, 403);
        }

        $token = $user->createToken('staff', ['staff'], now()->addHours(12))->plainTextToken;

        return $this->respondWithSuccess([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'roles' => $user->getRoleNames()],
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ], 'Signed in.');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return $this->respondWithSuccess(null, 'Signed out.');
    }

    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        $request->user()->currentAccessToken()?->delete();
        $token = $user->createToken('staff', ['staff'], now()->addHours(12))->plainTextToken;

        return $this->respondWithSuccess(['token' => $token, 'token_type' => 'Bearer'], 'Token refreshed.');
    }
}
