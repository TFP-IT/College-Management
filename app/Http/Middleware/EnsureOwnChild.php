<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

/** On guardian pages, any student named in the request must be one of that guardian's children. */
class EnsureOwnChild
{
    public function handle(Request $request, Closure $next)
    {
        $named = [$request->input('student_id'), $request->input('user_id')];
        if ($request->routeIs('parent.student.id_card')) {
            $named[] = $request->route('id');
        }

        foreach (array_filter($named, fn ($id) => $id !== null && $id !== '') as $id) {
            abort_unless(
                is_scalar($id) && User::whereKey($id)->where('parent_id', $request->user()?->id)->exists(),
                404
            );
        }

        return $next($request);
    }
}
