<?php

namespace App\Modules\Campus\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Campus\Services\Notifier;
use App\Modules\Campus\Services\QuizService;
use App\Modules\Campus\Support\StudentContext;
use App\Services\TeacherClassroomService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class QuizController extends Controller
{
    public function __construct(private QuizService $quizzes)
    {
    }

    private function ok(array $data, int $status = 200)
    {
        return response()->json(['data' => $data], $status);
    }

    private function context(Request $request, User $student): StudentContext
    {
        abort_unless(StudentContext::canView($request->user(), $student), 404, 'Student not found.');

        return StudentContext::for($student);
    }

    /** A quiz is sat by the student alone; a guardian may follow it but not take it. */
    private function sitting(Request $request, User $student): StudentContext
    {
        abort_unless(StudentContext::isFamily($request->user(), $student), 404, 'Student not found.');
        $ctx = StudentContext::for($student);
        abort_unless($request->user()->id === $student->id, 403, 'Only the student can take a quiz, from their own sign-in.');

        return $ctx;
    }

    private function quizFor(StudentContext $ctx, int $id): object
    {
        $quiz = DB::table('quizzes')->where('id', $id)->where('class_id', $ctx->classId ?? 0)
            ->where(fn ($q) => $q->whereNull('section_id')->orWhere('section_id', $ctx->sectionId))->first();
        abort_unless($quiz && !($quiz->kind === 'spot' && Carbon::parse($quiz->opens_at)->isFuture()), 404, 'Quiz not found.');

        return $quiz;
    }

    public function index(Request $request, User $student)
    {
        $ctx = $this->context($request, $student);

        return $this->ok(['quizzes' => $ctx->enrolled() ? $this->quizzes->forStudent($ctx->classId, $ctx->sectionId, $student->id) : []]);
    }

    public function start(Request $request, User $student, int $quiz)
    {
        $ctx = $this->sitting($request, $student);

        return $this->ok($this->quizzes->start($this->quizFor($ctx, $quiz), $student->id));
    }

    public function submit(Request $request, User $student, int $quiz)
    {
        $ctx = $this->sitting($request, $student);
        $data = $request->validate(['answers' => 'present|array|max:100']);

        return $this->ok($this->quizzes->submit($this->quizFor($ctx, $quiz), $student->id, $data['answers']));
    }

    /** Answers are shown only after the quiz has closed for everyone. */
    public function review(Request $request, User $student, int $quiz)
    {
        $ctx = $this->context($request, $student);
        $item = $this->quizFor($ctx, $quiz);
        $attempt = DB::table('quiz_attempts')->where('quiz_id', $quiz)->where('student_id', $student->id)->first();
        abort_unless(Carbon::parse($item->closes_at)->isPast() && $attempt?->submitted_at, 422, 'Answers are shown after the quiz closes.');
        $given = json_decode($attempt->answers, true) ?: [];

        return $this->ok([
            'quiz' => $this->quizzes->summary($item),
            'score' => (float) $attempt->score,
            'max_score' => (float) $attempt->max_score,
            'questions' => $this->quizzes->questions($quiz)->map(fn ($q) => [
                'id' => (int) $q->id, 'type' => $q->type, 'prompt' => $q->prompt, 'marks' => (float) $q->marks,
                'options' => $q->options ? json_decode($q->options, true) : [], 'answer' => json_decode($q->answer, true),
                'given' => $given[$q->id] ?? null, 'correct' => $this->quizzes->correct($q, $given[$q->id] ?? null),
            ]),
        ]);
    }

    // ---- Teacher ----------------------------------------------------------

    public function teaching(Request $request)
    {
        $items = DB::table('quizzes')->where('teacher_id', $request->user()->id)->orderByDesc('id')->limit(50)->get();
        $stats = DB::table('quiz_attempts')->whereIn('quiz_id', $items->pluck('id'))->whereNotNull('submitted_at')->groupBy('quiz_id')
            ->selectRaw('quiz_id, count(*) as c, avg(score / nullif(max_score, 0) * 100) as average')->get()->keyBy('quiz_id');
        $subjects = DB::table('subjects')->whereIn('id', $items->pluck('subject_id'))->pluck('name', 'id');

        return $this->ok(['quizzes' => $items->map(fn ($q) => $this->quizzes->summary($q) + [
            'subject' => $subjects[$q->subject_id] ?? null,
            'submitted' => (int) ($stats[$q->id]->c ?? 0),
            'average' => isset($stats[$q->id]) ? (int) round($stats[$q->id]->average) : null,
        ])]);
    }

    public function results(Request $request, int $quiz, Notifier $notifier)
    {
        $item = DB::table('quizzes')->where('id', $quiz)->where('teacher_id', $request->user()->id)->first();
        abort_unless($item, 404);
        $attempts = DB::table('quiz_attempts')->where('quiz_id', $quiz)->get()->keyBy('student_id');
        $students = User::whereIn('id', $notifier->studentsIn($item->class_id, $item->section_id))->orderBy('name')->get(['id', 'name', 'student_id']);

        return $this->ok([
            'quiz' => $this->quizzes->summary($item),
            'students' => $students->map(fn ($s) => [
                'id' => $s->id, 'name' => $s->name, 'code' => $s->student_id,
                'submitted' => (bool) ($attempts[$s->id]->submitted_at ?? false),
                'score' => isset($attempts[$s->id]->score) ? (float) $attempts[$s->id]->score : null,
                'max_score' => isset($attempts[$s->id]) ? (float) $attempts[$s->id]->max_score : null,
            ]),
        ]);
    }

    public function store(Request $request, TeacherClassroomService $classroom, Notifier $notifier)
    {
        $data = $request->validate([
            'class_id' => 'required|integer', 'section_id' => 'required|integer', 'subject_id' => 'required|integer',
            'title' => 'required|string|max:160', 'kind' => ['required', Rule::in(QuizService::KINDS)], 'instructions' => 'nullable|string|max:1000',
            'opens_at' => 'required|date', 'closes_at' => 'required|date|after:opens_at|after:now', 'duration_minutes' => 'required|integer|min:1|max:240',
            'questions' => 'required|array|min:1|max:100',
            'questions.*.type' => ['required', Rule::in(QuizService::TYPES)], 'questions.*.prompt' => 'required|string|max:1000',
            'questions.*.marks' => 'required|numeric|min:0.5|max:100', 'questions.*.options' => 'nullable|array|max:8', 'questions.*.options.*' => 'required|string|max:300',
            'questions.*.answer' => 'present',
        ]);
        $teacher = $request->user();
        $assigned = $classroom->classes($teacher)->contains(fn ($c) => $c['class_id'] === (int) $data['class_id'] && $c['section_id'] === (int) $data['section_id'] && $c['subject_id'] === (int) $data['subject_id']);
        abort_unless($assigned, 403, 'This class is not assigned to you.');

        foreach ($data['questions'] as $i => $q) {
            $options = $q['options'] ?? [];
            $answer = $q['answer'] ?? null;
            $index = fn ($v) => is_numeric($v) && (int) $v >= 0 && (int) $v < count($options);
            $valid = match ($q['type']) {
                'choice' => count($options) >= 2 && $index($answer),
                'multiple' => count($options) >= 2 && is_array($answer) && $answer !== [] && collect($answer)->every($index),
                'true_false' => is_bool($answer),
                'numeric' => is_numeric($answer),
                'short' => is_string($answer) ? trim($answer) !== '' : (is_array($answer) && $answer !== []),
            };
            if (!$valid) {
                throw ValidationException::withMessages(["questions.{$i}.answer" => 'Question ' . ($i + 1) . ' needs a correct answer.']);
            }
        }

        $opens = Carbon::parse($data['opens_at'])->setTimezone(config('app.timezone'));
        $id = DB::transaction(function () use ($data, $teacher, $opens) {
            $id = DB::table('quizzes')->insertGetId(collect($data)->only(['class_id', 'section_id', 'subject_id', 'title', 'kind', 'instructions', 'duration_minutes'])->all() + [
                'school_id' => $teacher->school_id, 'teacher_id' => $teacher->id, 'opens_at' => $opens,
                'closes_at' => Carbon::parse($data['closes_at'])->setTimezone(config('app.timezone')), 'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach (array_values($data['questions']) as $i => $q) {
                DB::table('quiz_questions')->insert([
                    'quiz_id' => $id, 'position' => $i + 1, 'type' => $q['type'], 'prompt' => $q['prompt'], 'marks' => $q['marks'],
                    'options' => in_array($q['type'], ['choice', 'multiple'], true) ? json_encode(array_values($q['options'])) : null,
                    'answer' => json_encode($q['answer']),
                ]);
            }

            return $id;
        });

        // A spot test is unannounced by design.
        if ($data['kind'] !== 'spot') {
            $notifier->toFamilies($notifier->studentsIn($data['class_id'], $data['section_id']), 'quiz', 'New quiz', "{$data['title']} opens " . $opens->timezone('Asia/Dhaka')->format('j M, g:i A') . '.', ['screen' => 'quizzes']);
        }

        return $this->ok(['id' => $id], 201);
    }
}
