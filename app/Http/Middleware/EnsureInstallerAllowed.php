<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class EnsureInstallerAllowed
{
    /**
     * The installer exists only for a brand new site. It must be switched on explicitly, and once a
     * superadmin exists only the final "success" page can still be opened.
     */
    public function handle(Request $request, Closure $next)
    {
        abort_unless(config('security.installer_enabled'), 404);

        if (!$request->routeIs('success') && $this->alreadyInstalled()) {
            abort(404);
        }

        return $next($request);
    }

    private function alreadyInstalled(): bool
    {
        try {
            return Schema::hasTable('users') && User::where('role_id', 1)->exists();
        } catch (\Throwable $e) {
            // No database connection yet, which is the normal state before installation.
            return false;
        }
    }
}
