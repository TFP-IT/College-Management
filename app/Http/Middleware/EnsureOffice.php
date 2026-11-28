<?php

namespace App\Http\Middleware;

use App\Modules\Campus\Support\OfficeAccess;
use Closure;
use Illuminate\Http\Request;

class EnsureOffice
{
    public function handle(Request $request, Closure $next, string $area = 'dashboard')
    {
        abort_unless(OfficeAccess::allows($request->user(), $area), 403, 'Your account cannot open this part of the office portal.');

        return $next($request);
    }
}
