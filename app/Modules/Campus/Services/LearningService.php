<?php

namespace App\Modules\Campus\Services;

use App\Modules\Campus\Models\Assignment;
use App\Modules\Campus\Models\Lesson;
use App\Modules\Campus\Models\Submission;
use App\Modules\Campus\Support\StudentContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

/** Lessons and assignments as one student sees them. */
class LearningService
{
    public function lessonQuery(StudentContext $ctx)
    {
        return Lesson::where('class_id', $ctx->classId ?? 0)
            ->where(fn ($q) => $q->whereNull('section_id')->orWhere('section_id', $ctx->sectionId))
            ->where('school_id', $ctx->schoolId)
            ->where('status', 'published');
    }

    public function assignmentQuery(StudentContext $ctx)
    {
        return Assignment::where('class_id', $ctx->classId ?? 0)
            ->where(fn ($q) => $q->whereNull('section_id')->orWhere('section_id', $ctx->sectionId))
            ->where('school_id', $ctx->schoolId)
            ->whereNotNull('due_at');
    }

    /** Subjects of the class with lesson progress and syllabus coverage. */
    public function subjects(StudentContext $ctx): array
    {
        if (!$ctx->enrolled()) {
            return [];
        }

        $subjects = DB::table('subjects')
            ->where('class_id', $ctx->classId)
            ->when($ctx->sessionId, fn ($q) => $q->where('session_id', $ctx->sessionId))
            ->orderBy('name')
            ->get(['id', 'name']);

        $teachers = DB::table('routines')
            ->join('users', 'users.id', '=', 'routines.teacher_id')
            ->where('routines.class_id', $ctx->classId)
            ->where('routines.section_id', $ctx->sectionId)
            ->pluck('users.name', 'routines.subject_id');

        $lessons = $this->lessonQuery($ctx)->get(['id', 'subject_id']);
        $done = DB::table('lms_lesson_progress')->where('student_id', $ctx->student->id)
            ->whereIn('lesson_id', $lessons->pluck('id'))->pluck('lesson_id')->flip();
        $topics = DB::table('teacher_syllabus_topics')->where('class_id', $ctx->classId)
            ->where(fn ($q) => $q->whereNull('section_id')->orWhere('section_id', $ctx->sectionId))
            ->get(['subject_id', 'completed_at'])->groupBy('subject_id');

        return $subjects->map(function ($s) use ($teachers, $lessons, $done, $topics) {
            $mine = $lessons->where('subject_id', $s->id);
            $syllabus = $topics->get($s->id);

            return [
                'id' => (int) $s->id,
                'name' => $s->name,
                'teacher' => $teachers[$s->id] ?? null,
                'lessons' => $mine->count(),
                'lessons_done' => $mine->filter(fn ($l) => $done->has($l->id))->count(),
                'syllabus_percent' => $syllabus
                    ? (int) round($syllabus->whereNotNull('completed_at')->count() / $syllabus->count() * 100)
                    : null,
            ];
        })->all();
    }

    public function assignments(StudentContext $ctx): array
    {
        $items = $this->assignmentQuery($ctx)->orderByDesc('due_at')->limit(60)->get();
        $mine = Submission::where('student_id', $ctx->student->id)->whereIn('assignment_id', $items->pluck('id'))->get()->keyBy('assignment_id');
        $subjects = DB::table('subjects')->whereIn('id', $items->pluck('subject_id'))->pluck('name', 'id');
        $teachers = DB::table('users')->whereIn('id', $items->pluck('teacher_id'))->pluck('name', 'id');

        return $items->map(fn (Assignment $a) => $this->present(
            $a,
            $mine->get($a->id),
            $subjects[$a->subject_id] ?? null,
            $teachers[$a->teacher_id] ?? null,
        ))->all();
    }

    public function present(Assignment $a, ?Submission $s, ?string $subject, ?string $teacher): array
    {
        $closed = !$a->allow_late && $a->due_at->isPast();
        $state = match (true) {
            $s?->graded_at !== null => 'graded',
            $s !== null => 'submitted',
            $closed => 'missed',
            $a->due_at->isPast() => 'overdue',
            default => 'open',
        };

        return [
            'id' => $a->id,
            'title' => $a->title,
            'instructions' => $a->description,
            'subject' => $subject,
            'teacher' => $teacher,
            'total_marks' => (int) $a->total_marks,
            'due_at' => $a->due_at->toIso8601String(),
            'allow_late' => (bool) $a->allow_late,
            'state' => $state,
            'can_submit' => $s?->graded_at === null && !$closed,
            'submission' => $s ? [
                'body' => $s->body,
                'attachment_url' => $this->attachmentUrl($s),
                'submitted_at' => $s->submitted_at->toIso8601String(),
                'is_late' => $s->is_late,
                'marks' => $s->marks,
                'feedback' => $s->feedback,
            ] : null,
        ];
    }

    /** A short-lived link, so private student work is never reachable by guessing. */
    public function attachmentUrl(Submission $submission): ?string
    {
        if (!$submission->attachment_path) {
            return null;
        }

        return url(URL::temporarySignedRoute('campus.attachment', now()->addHours(6), ['submission' => $submission->id], false));
    }

    /** Keeps the counters the teacher dashboard reads in step with the real submissions. */
    public function refreshCounts(Assignment $assignment): void
    {
        $assignment->update([
            'submitted_count' => $assignment->submissions()->count(),
            'marked_count' => $assignment->submissions()->whereNotNull('graded_at')->count(),
        ]);
    }
}
