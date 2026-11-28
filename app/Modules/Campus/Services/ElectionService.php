<?php

namespace App\Modules\Campus\Services;

use App\Models\User;
use App\Modules\Campus\Support\StudentContext;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Prefect elections with a secret ballot: the voter list and the ballot box are separate tables that
 * share no key, time or order. Nobody, including administrators, can learn how a student voted.
 */
class ElectionService
{
    public function __construct(private Notifier $notifier)
    {
    }

    public function state(object $election): string
    {
        if ($election->published_at) {
            return 'published';
        }
        if (Carbon::parse($election->opens_at)->isFuture()) {
            return 'nominations';
        }

        return Carbon::parse($election->closes_at)->isFuture() ? 'open' : 'counting';
    }

    private function eligible(object $election, StudentContext $ctx): bool
    {
        return $ctx->enrolled() && (int) $ctx->schoolId === (int) $election->school_id
            && (!$election->class_id || (int) $election->class_id === $ctx->classId);
    }

    public function forStudent(StudentContext $ctx): array
    {
        return DB::table('elections')->where('school_id', $ctx->schoolId)->orderByDesc('opens_at')->limit(20)->get()
            ->filter(fn ($e) => $this->eligible($e, $ctx))
            ->map(function ($e) use ($ctx) {
                $state = $this->state($e);
                $mine = DB::table('election_candidates')->where('election_id', $e->id)->where('student_id', $ctx->student->id)->value('status');

                return [
                    'id' => (int) $e->id, 'title' => $e->title, 'position' => $e->position, 'description' => $e->description, 'state' => $state,
                    'opens_at' => Carbon::parse($e->opens_at)->toIso8601String(), 'closes_at' => Carbon::parse($e->closes_at)->toIso8601String(),
                    'voted' => DB::table('election_voters')->where('election_id', $e->id)->where('student_id', $ctx->student->id)->exists(),
                    'my_nomination' => $mine,
                    'candidates' => $this->candidates($e, $state === 'published'),
                ];
            })->values()->all();
    }

    /** Counts are only included once the result is published (or for the committee after voting closes). */
    public function candidates(object $election, bool $withVotes, bool $all = false): array
    {
        $votes = $withVotes ? DB::table('election_ballots')->where('election_id', $election->id)->groupBy('candidate_id')->selectRaw('candidate_id, count(*) as c')->pluck('c', 'candidate_id') : collect();

        $rows = DB::table('election_candidates')->join('users', 'users.id', '=', 'election_candidates.student_id')
            ->where('election_id', $election->id)->when(!$all, fn ($q) => $q->where('election_candidates.status', 'approved'))
            ->orderBy('users.name')->get(['election_candidates.id', 'users.name', 'users.student_id as code', 'election_candidates.student_id', 'statement', 'election_candidates.status']);

        return $rows->map(fn ($c) => [
            'id' => (int) $c->id, 'name' => $c->name, 'code' => $c->code, 'student_id' => (int) $c->student_id, 'statement' => $c->statement, 'status' => $c->status,
            'votes' => $withVotes ? (int) ($votes[$c->id] ?? 0) : null,
        ])->when($withVotes, fn ($c) => $c->sortByDesc('votes'))->values()->all();
    }

    private function find(StudentContext $ctx, int $electionId): object
    {
        $election = DB::table('elections')->where('id', $electionId)->where('school_id', $ctx->schoolId)->first();
        abort_unless($election && $this->eligible($election, $ctx), 404, 'Election not found.');

        return $election;
    }

    public function nominate(StudentContext $ctx, int $electionId, ?string $statement): void
    {
        $election = $this->find($ctx, $electionId);
        abort_unless($this->state($election) === 'nominations', 422, 'Nominations are closed.');
        abort_if(DB::table('election_candidates')->where('election_id', $electionId)->where('student_id', $ctx->student->id)->exists(), 422, 'You are already nominated.');

        DB::table('election_candidates')->insert(['election_id' => $electionId, 'student_id' => $ctx->student->id, 'statement' => $statement, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function vote(User $voter, StudentContext $ctx, int $electionId, int $candidateId): void
    {
        // A vote is personal: a guardian cannot cast it.
        abort_unless($voter->id === $ctx->student->id, 403, 'Only the student can vote, from their own sign-in.');
        $election = $this->find($ctx, $electionId);
        abort_unless($this->state($election) === 'open', 422, 'Voting is not open.');
        abort_unless(DB::table('election_candidates')->where('id', $candidateId)->where('election_id', $electionId)->where('status', 'approved')->exists(), 422, 'Choose one of the candidates.');

        try {
            DB::transaction(function () use ($electionId, $candidateId, $ctx) {
                DB::table('election_voters')->insert(['election_id' => $electionId, 'student_id' => $ctx->student->id]);
                DB::table('election_ballots')->insert(['id' => (string) Str::uuid(), 'election_id' => $electionId, 'candidate_id' => $candidateId]);
            });
        } catch (QueryException $e) {
            if (($e->errorInfo[1] ?? null) === 1062) {
                abort(422, 'You have already voted.');
            }
            throw $e;
        }
    }

    public function publish(object $election): void
    {
        abort_unless($this->state($election) === 'counting', 422, 'Results can be published after voting closes.');
        DB::table('elections')->where('id', $election->id)->update(['published_at' => now(), 'updated_at' => now()]);

        $students = DB::table('users')->where('school_id', $election->school_id)->where('role_id', 7)->where('status', 1)->pluck('id');
        $this->notifier->send($students, 'campus', 'Election result', "The result for {$election->position} is published.", ['screen' => 'elections']);
    }
}
