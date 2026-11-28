<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Superadmin, admin, teacher, accountant and librarian accounts. */
class EnsureStaff
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless(in_array((int) $request->user()?->role_id, [1, 2, 3, 4, 5], true), 403);

        return $next($request);
    }
}
