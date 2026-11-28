<?php

namespace App\Modules\Campus\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Gradebook;
use App\Models\User;
use App\Modules\Campus\Models\Assignment;
use App\Modules\Campus\Models\Lesson;
use App\Modules\Campus\Models\ResultPublication;
use App\Modules\Campus\Models\Submission;
use App\Modules\Campus\Services\AttendanceService;
use App\Modules\Campus\Services\LearningService;
use App\Modules\Campus\Services\Notifier;
use App\Modules\Campus\Services\ResultService;
use App\Modules\Campus\Services\TimetableService;
use App\Modules\Campus\Support\StudentContext;
use App\Services\TeacherClassroomService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/** Daily teaching work. Everything is limited to the classes the office assigned to this teacher. */
class TeachingController extends Controller
{
    public function __construct(private TeacherClassroomService $classroom, private Notifier $notifier)
    {
    }

    private function assigned(User $teacher)
    {
        return $this->classroom->classes($teacher);
    }

    /** Aborts unless the teacher has this class, section and (when given) subject. */
    private function assertAssigned(User $teacher, int $classId, int $sectionId, ?int $subjectId = null): void
    {
        $match = $this->assigned($teacher)->contains(fn ($c) => $c['class_id'] === $classId
            && $c['section_id'] === $sectionId
            && ($subjectId === null || $c['subject_id'] === $subjectId));

        abort_unless($match, 403, 'This class is not assigned to you.');
    }

    private function ok(array $data, int $status = 200)
    {
        return response()->json(['data' => $data], $status);
    }

    public function today(Request $request, TimetableService $timetables)
    {
        $teacher = $request->user();
        $session = StudentContext::runningSession((int) $teacher->school_id);
        $week = $timetables->week(['teacher_id' => $teacher->id, 'school_id' => $teacher->school_id, 'session_id' => $session]);
        $periods = collect($week)->firstWhere('day', TimetableService::todayKey())['periods'] ?? [];

        // A class section counts as marked once any attendance row exists for today.
        $start = now(AttendanceService::TIMEZONE)->startOfDay()->timestamp;
        $marked = DB::table('daily_attendances')->where('school_id', $teacher->school_id)
            ->whereBetween('timestamp', [$start, $start + 86399])
            ->get(['class_id', 'section_id'])
            ->map(fn ($r) => "{$r->class_id}-{$r->section_id}")->unique()->flip();

        $toMark = Submission::whereNull('graded_at')
            ->whereIn('assignment_id', Assignment::where('teacher_id', $teacher->id)->select('id'))
            ->count();

        return $this->ok([
            'date' => now(AttendanceService::TIMEZONE)->toDateString(),
            'today' => TimetableService::todayKey(),
            'periods' => collect($periods)->map(fn ($p) => $p + ['attendance_marked' => $marked->has("{$p['class_id']}-{$p['section_id']}")]),
            'week' => $week,
            'to_mark' => $toMark,
        ]);
    }

    public function classes(Request $request)
    {
        return $this->ok(['classes' => $this->assigned($request->user())]);
    }

    // ---- Assignments ------------------------------------------------------

    private function ownAssignment(Request $request, int $id): Assignment
    {
        return Assignment::where('teacher_id', $request->user()->id)->findOrFail($id);
    }

    private function assignmentRow(Assignment $a): array
    {
        $labels = DB::table('classes')->where('id', $a->class_id)->value('name');
        $section = DB::table('sections')->where('id', $a->section_id)->value('name');

        return [
            'id' => $a->id,
            'title' => $a->title,
            'instructions' => $a->description,
            'class_label' => trim($labels . ' ' . $section),
            'subject' => DB::table('subjects')->where('id', $a->subject_id)->value('name'),
            'total_marks' => (int) $a->total_marks,
            'due_at' => $a->due_at?->toIso8601String(),
            'total_students' => (int) $a->total_students,
            'submitted' => (int) $a->submitted_count,
            'marked' => (int) $a->marked_count,
        ];
    }

    public function assignments(Request $request)
    {
        $items = Assignment::where('teacher_id', $request->user()->id)->whereNotNull('due_at')->latest('due_at')->limit(50)->get();

        return $this->ok(['assignments' => $items->map(fn ($a) => $this->assignmentRow($a))]);
    }

    public function createAssignment(Request $request)
    {
        $data = $request->validate([
            'class_id' => 'required|integer',
            'section_id' => 'required|integer',
            'subject_id' => 'required|integer',
            'title' => 'required|string|max:160',
            'instructions' => 'nullable|string|max:5000',
            'total_marks' => 'required|integer|min:1|max:200',
            'due_at' => 'required|date|after:now',
            'allow_late' => 'boolean',
        ]);
        $teacher = $request->user();
        $this->assertAssigned($teacher, $data['class_id'], $data['section_id'], $data['subject_id']);
        $students = $this->notifier->studentsIn($data['class_id'], $data['section_id']);
        $due = \Carbon\Carbon::parse($data['due_at'])->setTimezone(config('app.timezone'));

        $assignment = Assignment::create([
            'teacher_id' => $teacher->id,
            'school_id' => $teacher->school_id,
            'class_id' => $data['class_id'],
            'section_id' => $data['section_id'],
            'subject_id' => $data['subject_id'],
            'title' => $data['title'],
            'description' => $data['instructions'] ?? null,
            'total_marks' => $data['total_marks'],
            'due_at' => $due,
            'due_date' => $due->toDateString(),
            'allow_late' => $data['allow_late'] ?? true,
            'total_students' => $students->count(),
        ]);

        $this->notifier->toFamilies(
            $students,
            'assignment',
            'New assignment',
            "{$data['title']} is due " . $due->timezone(AttendanceService::TIMEZONE)->format('d M, g:i A') . '.',
            ['screen' => 'assignments', 'assignment_id' => $assignment->id],
        );

        return $this->ok($this->assignmentRow($assignment->refresh()), 201);
    }

    public function submissions(Request $request, int $assignment, LearningService $learning)
    {
        $item = $this->ownAssignment($request, $assignment);
        $submitted = $item->submissions()->get()->keyBy('student_id');
        $students = User::whereIn('id', $this->notifier->studentsIn($item->class_id, $item->section_id)->merge($submitted->keys()))
            ->orderBy('name')->get(['id', 'name', 'student_id']);

        return $this->ok([
            'assignment' => $this->assignmentRow($item),
            'students' => $students->map(function (User $s) use ($submitted, $learning) {
                $work = $submitted->get($s->id);

                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'code' => $s->student_id,
                    'submission' => $work ? [
                        'id' => $work->id,
                        'body' => $work->body,
                        'attachment_url' => $learning->attachmentUrl($work),
                        'submitted_at' => $work->submitted_at->toIso8601String(),
                        'is_late' => $work->is_late,
                        'marks' => $work->marks,
                        'feedback' => $work->feedback,
                    ] : null,
                ];
            }),
        ]);
    }

    public function grade(Request $request, int $submission, LearningService $learning)
    {
        $work = Submission::findOrFail($submission);
        $assignment = $this->ownAssignment($request, $work->assignment_id);
        $data = $request->validate([
            'marks' => "required|numeric|min:0|max:{$assignment->total_marks}",
            'feedback' => 'nullable|string|max:1000',
        ]);

        $work->update($data + ['graded_by' => $request->user()->id, 'graded_at' => now()]);
        $learning->refreshCounts($assignment);
        $this->notifier->toFamilies([$work->student_id], 'assignment', 'Assignment marked', "{$assignment->title} has been marked.", ['screen' => 'assignments', 'assignment_id' => $assignment->id]);

        return $this->ok(['marks' => $work->marks, 'feedback' => $work->feedback]);
    }

    // ---- Lessons ----------------------------------------------------------

    public function lessons(Request $request)
    {
        $items = Lesson::where('teacher_id', $request->user()->id)->latest('id')->limit(60)->get();
        $subjects = DB::table('subjects')->whereIn('id', $items->pluck('subject_id'))->pluck('name', 'id');
        $classes = DB::table('classes')->whereIn('id', $items->pluck('class_id'))->pluck('name', 'id');
        $sections = DB::table('sections')->whereIn('id', $items->pluck('section_id'))->pluck('name', 'id');

        return $this->ok(['lessons' => $items->map(fn ($l) => [
            'id' => $l->id,
            'title' => $l->title,
            'chapter' => $l->chapter,
            'summary' => $l->summary,
            'subject' => $subjects[$l->subject_id] ?? null,
            'class_label' => trim(($classes[$l->class_id] ?? '') . ' ' . ($sections[$l->section_id] ?? '')),
            'published_at' => $l->published_at?->toIso8601String(),
        ])]);
    }

    public function createLesson(Request $request)
    {
        $data = $request->validate([
            'class_id' => 'required|integer',
            'section_id' => 'required|integer',
            'subject_id' => 'required|integer',
            'title' => 'required|string|max:160',
            'chapter' => 'nullable|string|max:120',
            'summary' => 'nullable|string|max:1000',
            'content' => 'nullable|string|max:20000',
            'link_url' => 'nullable|url|starts_with:https://,http://|max:500',
        ]);
        $teacher = $request->user();
        $this->assertAssigned($teacher, $data['class_id'], $data['section_id'], $data['subject_id']);

        $lesson = Lesson::create($data + [
            'teacher_id' => $teacher->id,
            'school_id' => $teacher->school_id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->notifier->toFamilies(
            $this->notifier->studentsIn($data['class_id'], $data['section_id']),
            'lesson',
            'New lesson',
            $data['title'],
            ['screen' => 'lessons', 'subject_id' => $data['subject_id']],
        );

        return $this->ok(['id' => $lesson->id], 201);
    }

    // ---- Marks and result submission --------------------------------------

    private function marksKey(Request $request, array $data): array
    {
        $school = (int) $request->user()->school_id;
        abort_unless(DB::table('exam_categories')->where('id', $data['exam_category_id'])->where('school_id', $school)->exists(), 404, 'Exam not found.');

        return [
            'exam_category_id' => $data['exam_category_id'],
            'class_id' => $data['class_id'],
            'section_id' => $data['section_id'],
            'school_id' => $school,
            'session_id' => (int) StudentContext::runningSession($school),
        ];
    }

    private function assertCanMark(Request $request, array $data): void
    {
        $this->assertAssigned($request->user(), $data['class_id'], $data['section_id']);
        $permitted = $this->assigned($request->user())->contains(fn ($c) => $c['class_id'] === $data['class_id']
            && $c['section_id'] === $data['section_id'] && $c['can_enter_marks']);
        abort_unless($permitted, 403, 'The office has not given you permission to enter marks for this class.');
    }

    public function exams(Request $request)
    {
        $school = (int) $request->user()->school_id;
        $session = StudentContext::runningSession($school);

        return $this->ok(['exams' => DB::table('exam_categories')->where('school_id', $school)
            ->when($session, fn ($q) => $q->where('session_id', $session))
            ->orderBy('id')->get(['id', 'name'])]);
    }

    private function sheetRules(): array
    {
        return [
            'exam_category_id' => 'required|integer',
            'class_id' => 'required|integer',
            'section_id' => 'required|integer',
            'subject_id' => 'required|integer',
        ];
    }

    private function ints(array $data): array
    {
        return array_map(fn ($v) => is_numeric($v) ? (int) $v : $v, $data);
    }

    public function marks(Request $request, ResultService $results)
    {
        $data = $this->ints($request->validate($this->sheetRules()));
        $this->assertCanMark($request, $data);
        $key = $this->marksKey($request, $data);

        $books = Gradebook::where($key)->get()->keyBy('student_id');
        $students = User::whereIn('id', $this->notifier->studentsIn($data['class_id'], $data['section_id']))->orderBy('name')->get(['id', 'name', 'student_id']);
        $publication = ResultPublication::where(collect($key)->except('school_id')->all())->first();

        return $this->ok([
            'status' => $publication?->status ?? 'draft',
            'note' => $publication?->note,
            'locked' => in_array($publication?->status, ['submitted', 'published'], true),
            'students' => $students->map(function (User $s) use ($books, $data, $key, $results) {
                $mark = json_decode($books->get($s->id)?->marks ?? '', true)[$data['subject_id']] ?? null;
                $mark = $mark === null || $mark === '' ? null : (float) $mark;

                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'code' => $s->student_id,
                    'mark' => $mark,
                    'grade' => $mark === null ? null : $results->grade($key['school_id'], $mark)['name'],
                ];
            }),
        ]);
    }

    public function saveMarks(Request $request)
    {
        $data = $request->validate($this->sheetRules() + [
            'marks' => 'required|array|min:1',
            'marks.*.student_id' => 'required|integer',
            'marks.*.mark' => 'nullable|numeric|min:0|max:100',
        ]);
        $head = $this->ints(collect($data)->except('marks')->all());
        $this->assertCanMark($request, $head);
        $this->assertAssigned($request->user(), $head['class_id'], $head['section_id'], $head['subject_id']);
        $key = $this->marksKey($request, $head);

        $publication = ResultPublication::where(collect($key)->except('school_id')->all())->first();
        abort_if(in_array($publication?->status, ['submitted', 'published'], true), 423, 'These results are with the office and can no longer be changed.');

        $allowed = $this->notifier->studentsIn($head['class_id'], $head['section_id'])->flip();

        DB::transaction(function () use ($data, $head, $key, $allowed) {
            foreach ($data['marks'] as $row) {
                abort_unless($allowed->has((int) $row['student_id']), 422, 'A student on this list is not enrolled in the section.');

                $book = Gradebook::where($key + ['student_id' => $row['student_id']])->lockForUpdate()->first();
                $marks = $book ? (json_decode($book->marks, true) ?: []) : [];
                $marks[$head['subject_id']] = $row['mark'] ?? null;

                if ($book) {
                    $book->update(['marks' => json_encode($marks)]);
                } else {
                    Gradebook::create($key + ['student_id' => $row['student_id'], 'marks' => json_encode($marks), 'timestamp' => strtotime(date('Y-m-d'))]);
                }
            }
        });

        Log::info('Marks saved', ['by' => $request->user()->id] + $head + ['rows' => count($data['marks'])]);

        return $this->ok(['saved' => count($data['marks'])]);
    }

    public function submitResults(Request $request)
    {
        $data = $this->ints($request->validate(collect($this->sheetRules())->except('subject_id')->all()));
        $this->assertCanMark($request, $data);
        $key = $this->marksKey($request, $data);

        abort_unless(Gradebook::where($key)->exists(), 422, 'Enter marks before sending results to the office.');

        $publication = ResultPublication::firstOrNew(collect($key)->except('school_id')->all());
        abort_if($publication->status === 'published', 423, 'These results are already published.');
        $publication->fill([
            'school_id' => $key['school_id'],
            'status' => 'submitted',
            'submitted_by' => $request->user()->id,
            'submitted_at' => now(),
            'note' => null,
        ])->save();

        return $this->ok(['status' => 'submitted']);
    }
}
