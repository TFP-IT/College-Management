<?php

namespace App\Http\Middleware;

use App\Models\MessageThrade;
use Closure;
use Illuminate\Http\Request;

/** A conversation can only be opened or written to by the two people in it. */
class EnsureThreadParticipant
{
    public function handle(Request $request, Closure $next)
    {
        $threadId = $request->route('id') ?? $request->input('message_thrade');
        $me = $request->user()?->id;

        $mine = is_scalar($threadId) && MessageThrade::whereKey($threadId)
            ->where(fn ($q) => $q->where('sender_id', $me)->orWhere('reciver_id', $me))
            ->exists();

        abort_unless($mine, 404);

        return $next($request);
    }
}
