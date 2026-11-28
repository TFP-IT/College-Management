<?php

namespace App\Modules\Campus\Services;

use App\Modules\Campus\Support\StudentContext;
use Illuminate\Support\Facades\DB;

/**
 * The student performance picture (specification Phase 2, sections 36-40). Every number shows its
 * source, the star rating is a published weighted average, and alerts are plain rules. Nothing here
 * decides anything about a student; it points a person at who may need help.
 */
class ProgressService
{
    /** Percent weights. A part with no data is left out and the rest are rescaled. */
    private const WEIGHTS = ['exams' => 35, 'attendance' => 20, 'assignments' => 20, 'preparation' => 10, 'assimilation' => 10, 'tests' => 5];

    public function __construct(private AttendanceService $attendance, private ResultService $results, private LearningService $learning)
    {
    }

    public function forStudent(StudentContext $ctx): array
    {
        $attendance = $this->attendance->forStudent($ctx)['session'];
        $results = $this->results->publishedFor($ctx);
        $assignments = collect($this->learning->assignments($ctx));
        $due = $assignments->filter(fn ($a) => $a['state'] !== 'open');
        $graded = $assignments->where('state', 'graded');
        $quizzes = DB::table('quiz_attempts')->join('quizzes', 'quizzes.id', '=', 'quiz_attempts.quiz_id')
            ->where('student_id', $ctx->student->id)->whereNotNull('submitted_at')->where('max_score', '>', 0)
            ->get(['quizzes.kind', 'score', 'max_score']);
        $quiz = fn (array $kinds) => ($set = $quizzes->whereIn('kind', $kinds))->isEmpty() ? null : $set->avg(fn ($q) => $q->score / $q->max_score * 100);
        $marked = $graded->isEmpty() ? null : $graded->avg(fn ($a) => $a['submission']['marks'] / max(1, $a['total_marks']) * 100);
        $handed = $due->isEmpty() ? null : $due->whereIn('state', ['submitted', 'graded'])->count() / $due->count() * 100;

        $values = [
            'exams' => ['Published exams', $results ? $results[0]['average'] : null, $results ? $results[0]['exam'] : null],
            'attendance' => ['Attendance', $attendance['rate'], $attendance['total'] ? "{$attendance['total']} days recorded" : null],
            'assignments' => ['Assignments', $handed === null ? $marked : ($marked === null ? $handed : ($handed + $marked) / 2), $due->count() ? $due->whereIn('state', ['submitted', 'graded'])->count() . " of {$due->count()} handed in" : null],
            'preparation' => ['Preparatory tests', $quiz(['preparatory']), 'Before class'],
            'assimilation' => ['Assimilation tests', $quiz(['assimilation']), 'During class'],
            'tests' => ['Quizzes and tests', $quiz(['quiz', 'spot', 'class_test']), 'Class and spot tests'],
        ];

        $parts = collect($values)->map(fn ($v, $key) => [
            'key' => $key, 'label' => $v[0], 'value' => $v[1] === null ? null : (int) round($v[1]),
            'note' => $v[1] === null ? 'No data yet' : $v[2], 'weight' => self::WEIGHTS[$key],
        ])->values();
        $known = $parts->whereNotNull('value');
        $overall = $known->isEmpty() ? null : $known->sum(fn ($p) => $p['value'] * $p['weight']) / $known->sum('weight');

        $trend = null;
        if (count($results) > 1) {
            $change = $results[0]['average'] - $results[1]['average'];
            $trend = $change >= 3 ? 'improving' : ($change <= -3 ? 'declining' : 'stable');
        }

        $alerts = [];
        if ($attendance['rate'] !== null && $attendance['rate'] < 80) {
            $alerts[] = "Attendance is {$attendance['rate']}%, below 80%.";
        }
        if (($missing = $assignments->whereIn('state', ['overdue', 'missed'])->count()) >= 3) {
            $alerts[] = "{$missing} assignments have not been handed in.";
        }
        foreach ($this->decliningSubjects($results) as $subject) {
            $alerts[] = "{$subject} has fallen in each of the last three exams.";
        }

        return [
            'overall' => $overall === null ? null : (int) round($overall),
            'stars' => $overall === null ? null : round($overall / 20, 1),
            'trend' => $trend,
            'parts' => $parts->all(),
            'alerts' => $alerts,
        ];
    }

    private function decliningSubjects(array $results): array
    {
        if (count($results) < 3) {
            return [];
        }
        [$latest, $middle, $oldest] = array_map(fn ($r) => collect($r['subjects'])->keyBy('subject_id'), array_slice($results, 0, 3));

        return $latest->filter(fn ($s, $id) => isset($middle[$id], $oldest[$id]) && $s['mark'] < $middle[$id]['mark'] && $middle[$id]['mark'] < $oldest[$id]['mark'])
            ->pluck('subject')->values()->all();
    }

    /** Students of a school who currently have at least one alert. */
    public function needingAttention(int $schoolId, int $limit = 300): array
    {
        $students = \App\Models\User::where('school_id', $schoolId)->where('role_id', 7)->where('status', 1)->orderBy('name')->limit($limit)->get();

        return $students->map(function ($student) {
            $ctx = StudentContext::for($student);
            $progress = $ctx->enrolled() ? $this->forStudent($ctx) : null;

            return $progress && $progress['alerts'] ? [
                'id' => $student->id, 'name' => $student->name, 'code' => $student->student_id,
                'class' => trim("{$ctx->className} {$ctx->sectionName}"), 'overall' => $progress['overall'], 'alerts' => $progress['alerts'],
            ] : null;
        })->filter()->sortBy('overall')->values()->all();
    }
}
