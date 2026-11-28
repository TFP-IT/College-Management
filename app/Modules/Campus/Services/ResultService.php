<?php

namespace App\Modules\Campus\Services;

use App\Modules\Campus\Models\ResultPublication;
use App\Modules\Campus\Support\StudentContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Turns the marks in the gradebook into grades and GPA. The school's own grade table is used;
 * until one is set up, the national scale in config/academics.php applies.
 */
class ResultService
{
    private array $scales = [];

    /** @return Collection<int, array{name:string, point:float, from:float, to:float}> highest band first */
    public function scale(int $schoolId): Collection
    {
        return $this->scales[$schoolId] ??= $this->loadScale($schoolId);
    }

    private function loadScale(int $schoolId): Collection
    {
        $rows = DB::table('grades')->where('school_id', $schoolId)->get();

        if ($rows->isNotEmpty()) {
            return $rows->map(fn ($g) => [
                'name' => $g->name,
                'point' => (float) $g->grade_point,
                'from' => (float) $g->mark_from,
                'to' => (float) $g->mark_upto,
            ])->sortByDesc('from')->values();
        }

        return collect(config('academics.grading', []))->map(function ($g) {
            preg_match_all('/\d+/', $g['marks'], $m);

            return ['name' => $g['grade'], 'point' => (float) $g['point'], 'from' => (float) $m[0][0], 'to' => (float) $m[0][1]];
        })->sortByDesc('from')->values();
    }

    /** @return array{name:string, point:float} */
    public function grade(int $schoolId, float $percent): array
    {
        // Bands are matched on their lower bound, so 79.5 does not fall between 70-79 and 80-100.
        $band = $this->scale($schoolId)->first(fn ($b) => $percent >= $b['from']) ?? $this->scale($schoolId)->last();

        return ['name' => $band['name'] ?? '-', 'point' => $band['point'] ?? 0.0];
    }

    /**
     * Grades one gradebook row. Failing any subject fails the exam, following the national rule.
     *
     * @param array<int|string, mixed> $marks subject id => mark out of 100
     */
    public function compute(int $schoolId, array $marks, Collection $subjectNames): array
    {
        $subjects = collect($marks)
            ->filter(fn ($m) => $m !== null && $m !== '' && is_numeric($m))
            ->map(function ($mark, $subjectId) use ($schoolId, $subjectNames) {
                $grade = $this->grade($schoolId, (float) $mark);

                return [
                    'subject_id' => (int) $subjectId,
                    'subject' => $subjectNames[$subjectId] ?? 'Subject',
                    'mark' => round((float) $mark, 2),
                    'grade' => $grade['name'],
                    'point' => $grade['point'],
                ];
            })
            ->sortBy('subject')
            ->values();

        if ($subjects->isEmpty()) {
            return ['subjects' => [], 'total' => 0, 'average' => null, 'gpa' => null, 'grade' => null, 'passed' => null];
        }

        $passed = $subjects->every(fn ($s) => $s['point'] > 0);
        $gpa = $passed ? round($subjects->avg('point'), 2) : 0.0;
        $letter = $passed
            ? ($this->scale($schoolId)->first(fn ($b) => $gpa >= $b['point'])['name'] ?? null)
            : ($this->scale($schoolId)->last()['name'] ?? 'F');

        return [
            'subjects' => $subjects->all(),
            'total' => round($subjects->sum('mark'), 2),
            'average' => round($subjects->avg('mark'), 1),
            'gpa' => $gpa,
            'grade' => $letter,
            'passed' => $passed,
        ];
    }

    /** Published results only, newest first. Nothing is visible before approval. */
    public function publishedFor(StudentContext $ctx): array
    {
        $rows = DB::table('gradebooks')
            ->join('result_publications as p', function ($join) {
                $join->on('p.exam_category_id', '=', 'gradebooks.exam_category_id')
                    ->on('p.class_id', '=', 'gradebooks.class_id')
                    ->on('p.section_id', '=', 'gradebooks.section_id')
                    ->on('p.session_id', '=', 'gradebooks.session_id');
            })
            ->leftJoin('exam_categories', 'exam_categories.id', '=', 'gradebooks.exam_category_id')
            ->where('gradebooks.student_id', $ctx->student->id)
            ->where('p.status', 'published')
            ->orderByDesc('p.published_at')
            ->orderByDesc('p.id')
            ->get(['gradebooks.marks', 'gradebooks.comment', 'gradebooks.school_id', 'exam_categories.name as exam', 'p.published_at', 'p.id as publication_id']);

        $names = DB::table('subjects')->where('school_id', $ctx->schoolId)->pluck('name', 'id');

        return $rows->map(fn ($r) => [
            'id' => (int) $r->publication_id,
            'exam' => $r->exam ?: 'Examination',
            'published_at' => $r->published_at,
            'comment' => $r->comment,
        ] + $this->compute((int) $r->school_id, json_decode($r->marks, true) ?: [], $names))->values()->all();
    }

    /** Papers still to be sat, soonest first. The old exam screens store times as Unix stamps or text. */
    public function upcomingExams(StudentContext $ctx, int $limit = 10): array
    {
        if (!$ctx->enrolled()) {
            return [];
        }

        $rows = DB::table('exams')->where('class_id', $ctx->classId)->where('school_id', $ctx->schoolId)
            ->when($ctx->sessionId, fn ($q) => $q->where('session_id', $ctx->sessionId))->get();
        $subjects = DB::table('subjects')->whereIn('id', $rows->pluck('subject_id'))->pluck('name', 'id');
        $stamp = fn ($v) => $v === null || $v === '' ? null : (is_numeric($v) ? (int) $v : (strtotime((string) $v) ?: null));

        return $rows->map(function ($e) use ($subjects, $stamp) {
            $start = $stamp($e->starting_time);

            return $start && ($stamp($e->ending_time) ?? $start) >= now()->startOfDay()->timestamp ? [
                'id' => (int) $e->id,
                'category_id' => $e->exam_category_id ? (int) $e->exam_category_id : null,
                'name' => $e->name,
                'subject' => $subjects[$e->subject_id] ?? null,
                'starts_at' => date(DATE_ATOM, $start),
                'room' => $e->room_number ?: null,
                'total_marks' => (float) $e->total_marks,
            ] : null;
        })->filter()->sortBy('starts_at')->take($limit)->values()->all();
    }

    /** Class sheet used by the office to check marks before publishing. */
    public function sheet(ResultPublication $publication): array
    {
        $names = DB::table('subjects')->where('school_id', $publication->school_id)->pluck('name', 'id');

        $rows = DB::table('gradebooks')
            ->join('users', 'users.id', '=', 'gradebooks.student_id')
            ->where([
                'gradebooks.exam_category_id' => $publication->exam_category_id,
                'gradebooks.class_id' => $publication->class_id,
                'gradebooks.section_id' => $publication->section_id,
                'gradebooks.session_id' => $publication->session_id,
                'gradebooks.school_id' => $publication->school_id,
            ])
            ->orderBy('users.name')
            ->get(['users.id', 'users.name', 'users.student_id', 'gradebooks.marks']);

        return $rows->map(fn ($r) => [
            'student_id' => (int) $r->id,
            'name' => $r->name,
            'code' => $r->student_id,
        ] + $this->compute((int) $publication->school_id, json_decode($r->marks, true) ?: [], $names))->all();
    }
}
