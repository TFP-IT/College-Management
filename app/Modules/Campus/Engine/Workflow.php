<?php

namespace App\Modules\Campus\Engine;

use App\Models\User;
use App\Modules\Admissions\Services\AuditService;
use App\Modules\Campus\Services\Notifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The one approval engine. A request walks its steps in order; whoever raised it cannot approve it,
 * and one person cannot approve two steps of the same request.
 */
class Workflow
{
    public function __construct(private AuditService $audit, private Notifier $notifier)
    {
    }

    public function open(Module $module, object $record, array $steps, ?User $requester): void
    {
        DB::table('workflow_requests')->insert([
            'school_id' => $record->school_id,
            'subject_type' => $module->key,
            'subject_id' => $record->id,
            'title' => $record->title,
            'steps' => json_encode(array_values($steps)),
            'requested_by' => $requester?->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function forRecord(Module $module, int $recordId): ?object
    {
        $request = DB::table('workflow_requests')->where('subject_type', $module->key)->where('subject_id', $recordId)->orderByDesc('id')->first();
        if ($request) {
            $request->steps = json_decode($request->steps, true);
            $request->actions = DB::table('workflow_actions')->leftJoin('users', 'users.id', '=', 'workflow_actions.actor_id')
                ->where('request_id', $request->id)->orderBy('workflow_actions.id')
                ->get(['workflow_actions.*', 'users.name as actor']);
        }

        return $request;
    }

    public function canAct(User $user, object $request): bool
    {
        if ($request->status !== 'pending' || (int) $request->requested_by === (int) $user->id) {
            return false;
        }
        $steps = is_array($request->steps) ? $request->steps : json_decode($request->steps, true);

        return Access::canApproveStep($user, Module::find($request->subject_type), $steps[$request->step])
            && !DB::table('workflow_actions')->where('request_id', $request->id)->where('actor_id', $user->id)->exists();
    }

    /** @return \Illuminate\Support\Collection requests waiting for this person */
    public function inbox(User $user)
    {
        return DB::table('workflow_requests')->where('school_id', $user->school_id)->where('status', 'pending')->orderBy('id')->get()
            ->filter(fn ($r) => config("campus_modules.types.{$r->subject_type}") && $this->canAct($user, $r))
            ->values();
    }

    /** @return string pending, approved or rejected */
    public function act(int $requestId, User $user, bool $approve, ?string $note = null): string
    {
        return DB::transaction(function () use ($requestId, $user, $approve, $note) {
            $request = DB::table('workflow_requests')->where('id', $requestId)->where('school_id', $user->school_id)->lockForUpdate()->first();
            abort_unless($request, 404);
            abort_unless($this->canAct($user, $request), 403, 'This approval is not waiting for you.');

            if (!$approve && trim((string) $note) === '') {
                throw ValidationException::withMessages(['note' => 'Say why you are not approving it.']);
            }

            $module = Module::find($request->subject_type);
            $steps = json_decode($request->steps, true);
            $last = $request->step === count($steps) - 1;
            $outcome = !$approve ? 'rejected' : ($last ? 'approved' : 'pending');

            DB::table('workflow_actions')->insert(['request_id' => $request->id, 'step' => $request->step, 'actor_id' => $user->id, 'action' => $approve ? 'approved' : 'rejected', 'note' => $note]);
            DB::table('workflow_requests')->where('id', $request->id)->update([
                'step' => $approve && !$last ? $request->step + 1 : $request->step,
                'status' => $outcome,
                'decided_at' => $outcome === 'pending' ? null : now(),
                'updated_at' => now(),
            ]);
            $this->audit->record("workflow.{$outcome}", (string) $request->id, ['step' => $steps[$request->step]], ['subject' => "{$module->key}:{$request->subject_id}", 'note' => $note], $user, 'campus');

            if ($outcome !== 'pending') {
                $records = app(Records::class);
                $record = $records->find($module, $request->school_id, $request->subject_id);
                if ($outcome === 'approved') {
                    // Rules that must hold at the moment of approval. A failure rolls the approval back.
                    app(Effects::class)->approve($module, $record, $user);
                }
                $records->setStatus($module, $records->find($module, $request->school_id, $request->subject_id), $outcome, $user);
                $this->tell($module, $record, $outcome);
            }

            return $outcome;
        });
    }

    private function tell(Module $module, object $record, string $outcome): void
    {
        $title = $module->get('label') . ($outcome === 'approved' ? ' approved' : ' not approved');
        $data = ['screen' => 'record', 'type' => $module->key, 'record_id' => $record->id];

        if ($record->student_id) {
            $this->notifier->toFamilies([$record->student_id], 'approval', $title, $record->title, $data);
        } elseif ($record->staff_id || $record->created_by) {
            $this->notifier->send([$record->staff_id ?: $record->created_by], 'approval', $title, $record->title, $data);
        }
    }
}
