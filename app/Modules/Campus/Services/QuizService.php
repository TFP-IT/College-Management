<?php

namespace App\Modules\Campus\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/** Timed quizzes, marked by the server. Correct answers never leave it while a quiz is open. */
class QuizService
{
    public const KINDS = ['quiz', 'preparatory', 'assimilation', 'spot', 'class_test'];
    public const TYPES = ['choice', 'multiple', 'true_false', 'short', 'numeric'];
    private const GRACE_SECONDS = 30;

    public function state(object $quiz, ?object $attempt): string
    {
        if ($attempt?->submitted_at) {
            return 'done';
        }
        if (Carbon::parse($quiz->opens_at)->isFuture()) {
            return 'upcoming';
        }
        if ($attempt) {
            return Carbon::parse($attempt->expires_at)->isFuture() ? 'in_progress' : 'missed';
        }

        return Carbon::parse($quiz->closes_at)->isPast() ? 'missed' : 'open';
    }

    /** Spot tests stay invisible until they are released. */
    public function forStudent(int $classId, ?int $sectionId, int $studentId): array
    {
        $quizzes = DB::table('quizzes')->where('class_id', $classId)
            ->where(fn ($q) => $q->whereNull('section_id')->orWhere('section_id', $sectionId))
            ->where(fn ($q) => $q->where('kind', '!=', 'spot')->orWhere('opens_at', '<=', now()))
            ->orderByDesc('opens_at')->limit(50)->get();
        $attempts = DB::table('quiz_attempts')->where('student_id', $studentId)->whereIn('quiz_id', $quizzes->pluck('id'))->get()->keyBy('quiz_id');
        $subjects = DB::table('subjects')->whereIn('id', $quizzes->pluck('subject_id'))->pluck('name', 'id');
        $counts = DB::table('quiz_questions')->whereIn('quiz_id', $quizzes->pluck('id'))->groupBy('quiz_id')->selectRaw('quiz_id, count(*) as c')->pluck('c', 'quiz_id');

        return $quizzes->map(function ($q) use ($attempts, $subjects, $counts) {
            $attempt = $attempts->get($q->id);

            return $this->summary($q) + [
                'subject' => $subjects[$q->subject_id] ?? null,
                'questions' => (int) ($counts[$q->id] ?? 0),
                'state' => $this->state($q, $attempt),
                'score' => $attempt?->score === null ? null : (float) $attempt->score,
                'max_score' => $attempt ? (float) $attempt->max_score : null,
            ];
        })->all();
    }

    public function summary(object $q): array
    {
        return [
            'id' => (int) $q->id,
            'title' => $q->title,
            'kind' => $q->kind,
            'instructions' => $q->instructions,
            'opens_at' => Carbon::parse($q->opens_at)->toIso8601String(),
            'closes_at' => Carbon::parse($q->closes_at)->toIso8601String(),
            'duration_minutes' => (int) $q->duration_minutes,
        ];
    }

    /** Starts (or resumes) the single attempt and returns the questions without answers. */
    public function start(object $quiz, int $studentId): array
    {
        $attempt = DB::transaction(function () use ($quiz, $studentId) {
            $attempt = DB::table('quiz_attempts')->where('quiz_id', $quiz->id)->where('student_id', $studentId)->lockForUpdate()->first();
            if (!$attempt) {
                abort_unless($this->state($quiz, null) === 'open', 422, 'This quiz is not open.');
                // The clock stops at the closing time even if the student started late.
                $expires = now()->addMinutes($quiz->duration_minutes)->min(Carbon::parse($quiz->closes_at));
                DB::table('quiz_attempts')->insert([
                    'quiz_id' => $quiz->id, 'student_id' => $studentId, 'started_at' => now(), 'expires_at' => $expires,
                    'max_score' => DB::table('quiz_questions')->where('quiz_id', $quiz->id)->sum('marks'),
                ]);
                $attempt = DB::table('quiz_attempts')->where('quiz_id', $quiz->id)->where('student_id', $studentId)->first();
            }

            return $attempt;
        });

        abort_unless($this->state($quiz, $attempt) === 'in_progress', 422, 'This attempt is already finished.');

        return [
            'quiz' => $this->summary($quiz),
            'expires_at' => Carbon::parse($attempt->expires_at)->toIso8601String(),
            'seconds_left' => max(0, (int) now()->diffInSeconds(Carbon::parse($attempt->expires_at), false)),
            'questions' => $this->questions($quiz->id)->map(fn ($q) => [
                'id' => (int) $q->id, 'type' => $q->type, 'prompt' => $q->prompt, 'marks' => (float) $q->marks,
                'options' => $q->options ? json_decode($q->options, true) : [],
            ])->all(),
        ];
    }

    public function submit(object $quiz, int $studentId, array $answers): array
    {
        return DB::transaction(function () use ($quiz, $studentId, $answers) {
            $attempt = DB::table('quiz_attempts')->where('quiz_id', $quiz->id)->where('student_id', $studentId)->lockForUpdate()->first();
            abort_unless($attempt, 422, 'Start the quiz first.');
            abort_if((bool) $attempt->submitted_at, 422, 'You have already handed this in.');

            $late = now()->gt(Carbon::parse($attempt->expires_at)->addSeconds(self::GRACE_SECONDS));
            $kept = $late ? [] : $answers;
            $score = $this->questions($quiz->id)->sum(fn ($q) => $this->correct($q, $kept[$q->id] ?? null) ? (float) $q->marks : 0);

            DB::table('quiz_attempts')->where('id', $attempt->id)->update(['submitted_at' => now(), 'answers' => json_encode((object) $kept), 'score' => $score]);

            return ['score' => (float) $score, 'max_score' => (float) $attempt->max_score, 'late' => $late];
        });
    }

    public function correct(object $question, mixed $given): bool
    {
        $answer = json_decode($question->answer, true);
        if ($given === null || $given === '' || $given === []) {
            return false;
        }

        return match ($question->type) {
            'choice' => is_numeric($given) && (int) $given === (int) $answer,
            'true_false' => is_bool($given) && $given === (bool) $answer,
            'numeric' => is_numeric($given) && abs((float) $given - (float) $answer) < 0.001,
            'multiple' => is_array($given) && collect($given)->map(fn ($v) => (int) $v)->unique()->sort()->values()->all() === collect($answer)->sort()->values()->all(),
            'short' => is_string($given) && collect((array) $answer)->contains(fn ($a) => mb_strtolower(trim($a)) === mb_strtolower(trim($given))),
            default => false,
        };
    }

    public function questions(int $quizId)
    {
        return DB::table('quiz_questions')->where('quiz_id', $quizId)->orderBy('position')->get();
    }
}
