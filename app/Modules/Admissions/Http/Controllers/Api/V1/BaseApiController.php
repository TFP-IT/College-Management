<?php

namespace App\Modules\Admissions\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Admissions\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;

abstract class BaseApiController extends Controller
{
    /** Standard success envelope: { success, message, data, meta, errors } */
    protected function respondWithSuccess(mixed $data = null, ?string $message = null, array $meta = [], int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => array_merge(['timestamp' => now()->toIso8601String()], $meta),
            'errors' => null,
        ], $statusCode);
    }

    /** Standard error envelope. */
    protected function respondWithError(string $message, mixed $errors = null, int $statusCode = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'meta' => ['timestamp' => now()->toIso8601String()],
            'errors' => $errors,
        ], $statusCode);
    }

    /**
     * Owner of the application, or a user with the permission, may access it.
     * Anyone else gets 404 so application numbers cannot be probed.
     */
    protected function authorizeApplication(Request $request, Application $application, ?string $permission = null): void
    {
        $user = $request->user();

        if ($user && (string) $application->applicant_user_id === (string) $user->id) {
            return;
        }
        if ($permission && $user && $user->can($permission)) {
            return;
        }
        if (!$permission && $user && $user->can('admission.application.view')) {
            return;
        }

        abort(404);
    }

    /** Converts module exceptions into the JSON envelope, so web-style redirects never happen for API calls. */
    protected function guard(callable $callback): JsonResponse
    {
        try {
            return $callback();
        } catch (ValidationException $e) {
            return $this->respondWithError('Validation failed', $e->errors(), 422);
        } catch (InvalidArgumentException $e) {
            return $this->respondWithError($e->getMessage(), null, 422);
        } catch (RuntimeException $e) {
            return $this->respondWithError($e->getMessage(), null, 503);
        }
    }

    protected function notFoundIfMissing(mixed $value): void
    {
        if ($value === null) {
            throw new HttpException(404, 'Not found.');
        }
    }
}
