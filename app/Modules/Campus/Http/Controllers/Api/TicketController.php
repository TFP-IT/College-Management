<?php

namespace App\Modules\Campus\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Campus\Models\ServiceTicket;
use App\Modules\Campus\Support\StudentContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** The help desk for students, guardians and staff. People see only their own requests. */
class TicketController extends Controller
{
    public static function present(ServiceTicket $t, bool $withMessages = false): array
    {
        $data = [
            'id' => $t->id,
            'number' => 'SR-' . str_pad((string) $t->id, 5, '0', STR_PAD_LEFT),
            'category' => $t->category,
            'subject' => $t->subject,
            'priority' => $t->priority,
            'status' => $t->status,
            'created_at' => $t->created_at->toIso8601String(),
            'updated_at' => $t->updated_at->toIso8601String(),
        ];

        if ($withMessages) {
            $data['messages'] = $t->messages()->with('author:id,name')->orderBy('id')->get()->map(fn ($m) => [
                'id' => $m->id,
                'body' => $m->body,
                'author' => $m->author?->name,
                'from_staff' => $m->from_staff,
                'created_at' => $m->created_at->toIso8601String(),
            ]);
        }

        return $data;
    }

    private function mine(Request $request, int $ticket): ServiceTicket
    {
        return ServiceTicket::where('requester_id', $request->user()->id)->findOrFail($ticket);
    }

    public function index(Request $request)
    {
        $items = ServiceTicket::where('requester_id', $request->user()->id)->latest('updated_at')->limit(50)->get();

        return response()->json(['data' => [
            'categories' => ServiceTicket::CATEGORIES,
            'tickets' => $items->map(fn ($t) => self::present($t)),
        ]]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category' => ['required', Rule::in(ServiceTicket::CATEGORIES)],
            'subject' => 'required|string|max:160',
            'message' => 'required|string|max:3000',
            'student_id' => 'nullable|integer',
        ]);
        $user = $request->user();

        if (!empty($data['student_id'])) {
            abort_unless(($student = User::find($data['student_id'])) && StudentContext::isFamily($user, $student), 404);
        }

        $ticket = ServiceTicket::create([
            'school_id' => $user->school_id,
            'requester_id' => $user->id,
            'student_id' => $data['student_id'] ?? null,
            'category' => $data['category'],
            'subject' => $data['subject'],
            'priority' => $data['category'] === 'welfare' ? 'high' : 'normal',
            'status' => 'open',
        ]);
        $ticket->messages()->create(['user_id' => $user->id, 'body' => $data['message']]);

        return response()->json(['data' => self::present($ticket, true)], 201);
    }

    public function show(Request $request, int $ticket)
    {
        return response()->json(['data' => self::present($this->mine($request, $ticket), true)]);
    }

    public function reply(Request $request, int $ticket)
    {
        $item = $this->mine($request, $ticket);
        abort_if($item->status === 'closed', 422, 'This request is closed. Please open a new one.');
        $data = $request->validate(['message' => 'required|string|max:3000']);

        $item->messages()->create(['user_id' => $request->user()->id, 'body' => $data['message']]);
        $item->update(['status' => 'open', 'resolved_at' => null]);
        $item->touch();

        return response()->json(['data' => self::present($item->fresh(), true)]);
    }
}
