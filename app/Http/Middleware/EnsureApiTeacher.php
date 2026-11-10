<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureApiTeacher
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user || (int) $user->role_id !== 3 || (int) $user->status !== 1 || $user->account_status === 'disable') {
            return response()->json([
                'status' => 'error',
                'message' => 'This section is available to active teachers only.',
            ], 403);
        }

        return $next($request);
    }
}
