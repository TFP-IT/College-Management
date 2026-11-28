<?php

namespace App\Modules\Campus\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Campus\Models\CampusNotification;
use App\Modules\Campus\Models\Submission;
use App\Modules\Campus\Services\NoticeService;
use App\Modules\Campus\Support\StudentContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** Who is signed in, their notices and their notifications. */
class MeController extends Controller
{
    public const ROLES = [1 => 'superadmin', 2 => 'admin', 3 => 'teacher', 4 => 'accountant', 5 => 'librarian', 6 => 'guardian', 7 => 'student'];

    public static function studentCard(User $student, ?StudentContext $ctx = null): array
    {
        $ctx ??= StudentContext::for($student);
        $level = (string) $ctx->className;

        return [
            'id' => $student->id,
            'name' => $student->name,
            'code' => $student->student_id ?: 'MCC' . $student->id,
            'class_name' => $ctx->className,
            'section_name' => $ctx->sectionName,
            'photo_url' => StudentContext::photoUrl($student),
            // Playgroup to Class 5 are run mainly from the guardian account.
            'primary' => (bool) preg_match('/play|nursery|kg|kinder|class\s*(one|two|three|four|five|[1-5])\b/i', $level),
            'enrolled' => $ctx->enrolled(),
        ];
    }

    public function show(Request $request)
    {
        $user = $request->user();
        $role = self::ROLES[(int) $user->role_id] ?? 'user';

        return response()->json(['data' => [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $role,
                'email' => $user->email,
                'phone' => $user->phone_number,
                'code' => $role === 'student' ? ($user->student_id ?: 'MCC' . $user->id) : $user->teacher_id,
                'designation' => $user->designation,
                'photo_url' => StudentContext::photoUrl($user),
                'details' => $this->details($user),
            ],
            'student' => $role === 'student' ? self::studentCard($user) : null,
            'children' => $role === 'guardian'
                ? StudentContext::childrenOf($user)->map(fn (User $c) => self::studentCard($c))->values()
                : [],
            'unread' => CampusNotification::where('user_id', $user->id)->whereNull('read_at')->count(),
        ]]);
    }

    /** @return array<int, array{label:string, value:string}> only what is actually on record */
    private function details(User $user): array
    {
        $info = json_decode((string) $user->user_information, true) ?: [];
        $guardian = (int) $user->role_id === 7 && $user->parent_id ? User::find($user->parent_id) : null;

        return collect([
            'Guardian' => $guardian?->name,
            'Guardian mobile' => $guardian?->phone_number,
            'National ID' => $info['nid'] ?? null,
            'Occupation' => $info['occupation'] ?? null,
            'Blood group' => $info['blood_group'] ?? null,
            'Address' => $info['address'] ?? null,
        ])->filter(fn ($v) => is_string($v) && !in_array(trim($v), ['', 'N/A'], true))
            ->map(fn ($v, $label) => ['label' => $label, 'value' => $v])
            ->values()->all();
    }

    public function notices(Request $request, NoticeService $notices)
    {
        return response()->json(['data' => ['notices' => $notices->latest((int) $request->user()->school_id, 40)]]);
    }

    public function notifications(Request $request)
    {
        $items = CampusNotification::where('user_id', $request->user()->id)->latest('id')->limit(60)->get();

        return response()->json(['data' => [
            'unread' => $items->whereNull('read_at')->count(),
            'items' => $items->map(fn ($n) => [
                'id' => $n->id,
                'category' => $n->category,
                'title' => $n->title,
                'body' => $n->body,
                'data' => $n->data ?: (object) [],
                'read' => $n->read_at !== null,
                'created_at' => $n->created_at->toIso8601String(),
            ]),
        ]]);
    }

    public function markRead(Request $request)
    {
        $data = $request->validate(['ids' => 'nullable|array', 'ids.*' => 'integer']);

        CampusNotification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->when($data['ids'] ?? null, fn ($q, $ids) => $q->whereIn('id', $ids))
            ->update(['read_at' => now()]);

        return response()->json(['data' => ['ok' => true]]);
    }

    /** Signed link only: the link itself is the permission, and it expires. */
    public function attachment(Request $request, Submission $submission)
    {
        abort_unless($request->hasValidSignature(false) && $submission->attachment_path, 404);
        abort_unless(Storage::disk('local')->exists($submission->attachment_path), 404);

        return Storage::disk('local')->response($submission->attachment_path);
    }
}
