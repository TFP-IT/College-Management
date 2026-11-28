<?php

namespace App\Modules\Campus\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Campus\Engine\Module;
use App\Modules\Campus\Engine\Portal;
use App\Modules\Campus\Engine\Records;
use App\Modules\Campus\Services\ElectionService;
use App\Modules\Campus\Services\LearningService;
use App\Modules\Campus\Services\LibraryService;
use App\Modules\Campus\Services\Notifier;
use App\Modules\Campus\Services\ProgressService;
use App\Modules\Campus\Services\QuizService;
use App\Modules\Campus\Services\ResultService;
use App\Modules\Campus\Support\StudentContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Progress, calendar, portfolio, library, elections and secure messages. */
class LifeController extends Controller
{
    private function ok(array $data, int $status = 200)
    {
        return response()->json(['data' => $data], $status);
    }

    private function context(Request $request, User $student, bool $acting = false): StudentContext
    {
        $allowed = $acting ? StudentContext::isFamily($request->user(), $student) : StudentContext::canView($request->user(), $student);
        abort_unless($allowed, 404, 'Student not found.');

        return StudentContext::for($student);
    }

    public function progress(Request $request, User $student, ProgressService $progress)
    {
        return $this->ok($progress->forStudent($this->context($request, $student)));
    }

    /** One dated list: college calendar, exams, deadlines, quizzes and fee dates. */
    public function calendar(Request $request, User $student, LearningService $learning, QuizService $quizzes)
    {
        $ctx = $this->context($request, $student);
        $from = now('Asia/Dhaka')->startOfDay();
        $items = collect();
        $add = fn (string $kind, string $title, $date, ?string $note = null) => $date && Carbon::parse($date)->gte($from)
            ? $items->push(['kind' => $kind, 'title' => $title, 'date' => Carbon::parse($date)->toIso8601String(), 'note' => $note]) : null;

        foreach (DB::table('campus_records')->where('school_id', $ctx->schoolId)->where('type', 'calendar_event')->where('status', 'published')->get() as $e) {
            $add(json_decode($e->data, true)['kind'] ?? 'event', $e->title, $e->starts_at, json_decode($e->data, true)['details'] ?? null);
        }
        if ($ctx->enrolled()) {
            $subjects = DB::table('subjects')->where('class_id', $ctx->classId)->pluck('name', 'id');
            foreach (DB::table('exams')->where('class_id', $ctx->classId)->where('school_id', $ctx->schoolId)->get() as $e) {
                $add('exam', trim($e->name . ': ' . ($subjects[$e->subject_id] ?? ''), ': '), is_numeric($e->starting_time) ? Carbon::createFromTimestamp((int) $e->starting_time) : $e->starting_time, $e->room_number);
            }
            foreach ($learning->assignmentQuery($ctx)->get() as $a) {
                $add('assignment', $a->title, $a->due_at, 'Due');
            }
            foreach ($quizzes->forStudent($ctx->classId, $ctx->sectionId, $student->id) as $q) {
                $add('quiz', $q['title'], $q['opens_at'], 'Opens');
            }
        }
        if (StudentContext::isFamily($request->user(), $student)) {
            foreach (DB::table('student_fee_managers')->where('student_id', $student->id)->where('status', '!=', 'paid')->whereNotNull('due_on')->get() as $f) {
                $add('fee', $f->title, $f->due_on, 'Payment due');
            }
        }

        return $this->ok(['events' => $items->sortBy('date')->values()->take(80)]);
    }

    /** The student's verified record outside the classroom, in one place. */
    public function portfolio(Request $request, User $student, Records $records, ResultService $results)
    {
        $ctx = $this->context($request, $student);
        $family = StudentContext::isFamily($request->user(), $student);
        $section = function (string $type, array $statuses) use ($records, $ctx, $student) {
            $module = Module::find($type);

            return $records->query($module, $ctx->schoolId)->where('student_id', $student->id)->whereIn('status', $statuses)->orderByDesc('id')->get()
                ->map(fn ($r) => $records->present($module, $records->normalise($module, $r), false, []));
        };
        $sections = [
            ['title' => 'Achievements and badges', 'items' => $section('achievement', ['verified'])],
            ['title' => 'Certificates', 'items' => $section('credential', ['active'])],
            ['title' => 'Clubs and teams', 'items' => $section('activity_membership', ['approved'])],
            ['title' => 'Projects', 'items' => $section('innovation_project', ['approved', 'in_progress', 'testing', 'completed', 'showcased', 'awarded'])],
            ['title' => 'Competitions', 'items' => $section('competition_entry', ['participated', 'winner'])],
            ['title' => 'Commendations', 'items' => $section('commendation', ['recorded'])],
        ];
        if ($family) {
            $sections[] = ['title' => 'English and IELTS', 'items' => $section('ielts_test', ['recorded'])];
            $sections[] = ['title' => 'Goals', 'items' => $section('goal', ['active', 'completed', 'revised'])];
        }

        return $this->ok([
            'student' => MeController::studentCard($student, $ctx),
            'results' => collect($results->publishedFor($ctx))->map(fn ($r) => ['exam' => $r['exam'], 'gpa' => $r['gpa'], 'grade' => $r['grade']]),
            'sections' => collect($sections)->filter(fn ($s) => $s['items']->isNotEmpty())->values(),
        ]);
    }

    public function library(Request $request, User $student, LibraryService $library)
    {
        $ctx = $this->context($request, $student);

        return $this->ok([
            'loans' => $library->loans($student->id),
            'books' => $library->books($ctx->schoolId, $request->string('q')->limit(80, '')->toString() ?: null, 40),
        ]);
    }

    public function reserve(Request $request, User $student, int $book, LibraryService $library)
    {
        $ctx = $this->context($request, $student, true);
        $library->reserve($book, $ctx->schoolId, $student->id);

        return $this->ok(['reserved' => true], 201);
    }

    public function elections(Request $request, User $student, ElectionService $elections)
    {
        return $this->ok([
            'elections' => $elections->forStudent($this->context($request, $student)),
            'can_vote' => $request->user()->id === $student->id,
        ]);
    }

    public function nominate(Request $request, User $student, int $election, ElectionService $elections)
    {
        abort_unless($request->user()->id === $student->id, 403, 'Students nominate themselves from their own sign-in.');
        $data = $request->validate(['statement' => 'required|string|max:1000']);
        $elections->nominate($this->context($request, $student, true), $election, $data['statement']);

        return $this->ok(['nominated' => true], 201);
    }

    public function vote(Request $request, User $student, int $election, ElectionService $elections)
    {
        $data = $request->validate(['candidate_id' => 'required|integer']);
        $elections->vote($request->user(), $this->context($request, $student, true), $election, $data['candidate_id']);

        return $this->ok(['voted' => true], 201);
    }

    // ---- Secure messages ---------------------------------------------------

    private function conversation(Request $request, int $id): object
    {
        $me = $request->user()->id;
        $item = DB::table('conversations')->where('id', $id)->where(fn ($q) => $q->where('member_id', $me)->orWhere('staff_id', $me))->first();
        abort_unless($item, 404, 'Conversation not found.');

        return $item;
    }

    public function contacts(Request $request, User $student, Portal $portal)
    {
        $this->context($request, $student, true);

        return $this->ok(['contacts' => collect($portal->contacts($request->user(), $student))->map(fn ($label, $id) => ['id' => $id, 'label' => $label])->values()]);
    }

    public function conversations(Request $request)
    {
        $me = $request->user()->id;
        $items = DB::table('conversations')->where(fn ($q) => $q->where('member_id', $me)->orWhere('staff_id', $me))->orderByDesc('last_message_at')->limit(60)->get();
        $names = User::whereIn('id', $items->pluck('member_id')->merge($items->pluck('staff_id'))->merge($items->pluck('student_id')))->pluck('name', 'id');
        $unread = DB::table('conversation_messages')->whereIn('conversation_id', $items->pluck('id'))->where('sender_id', '!=', $me)->whereNull('read_at')
            ->groupBy('conversation_id')->selectRaw('conversation_id, count(*) as c')->pluck('c', 'conversation_id');

        return $this->ok(['conversations' => $items->map(fn ($c) => [
            'id' => $c->id, 'topic' => $c->topic,
            'with' => $names[$c->member_id === $me ? $c->staff_id : $c->member_id] ?? 'College',
            'about' => $c->student_id && $c->student_id !== $c->member_id ? ($names[$c->student_id] ?? null) : null,
            'unread' => (int) ($unread[$c->id] ?? 0), 'last_message_at' => Carbon::parse($c->last_message_at)->toIso8601String(),
        ])]);
    }

    /** Only families start a conversation, and only with that student's teachers and the office. */
    public function startConversation(Request $request, Portal $portal, Notifier $notifier)
    {
        $data = $request->validate(['student_id' => 'required|integer', 'staff_id' => 'required|integer', 'topic' => 'required|string|max:160', 'body' => 'required|string|max:3000']);
        $user = $request->user();
        $student = User::find($data['student_id']);
        abort_unless($student && StudentContext::isFamily($user, $student), 404, 'Student not found.');
        abort_unless(array_key_exists($data['staff_id'], $portal->contacts($user, $student)), 422, 'Choose a contact from the list.');

        $id = DB::transaction(function () use ($data, $user, $student) {
            $id = DB::table('conversations')->insertGetId(['school_id' => $student->school_id, 'student_id' => $student->id, 'member_id' => $user->id, 'staff_id' => $data['staff_id'], 'topic' => $data['topic'], 'last_message_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
            DB::table('conversation_messages')->insert(['conversation_id' => $id, 'sender_id' => $user->id, 'body' => $data['body'], 'created_at' => now(), 'updated_at' => now()]);

            return $id;
        });
        $notifier->send([$data['staff_id']], 'message', "Message from {$user->name}", $data['topic'], ['screen' => 'message', 'conversation_id' => $id]);

        return $this->ok(['id' => $id], 201);
    }

    public function showConversation(Request $request, int $conversation)
    {
        $item = $this->conversation($request, $conversation);
        $me = $request->user()->id;
        DB::table('conversation_messages')->where('conversation_id', $item->id)->where('sender_id', '!=', $me)->whereNull('read_at')->update(['read_at' => now()]);
        $names = User::whereIn('id', [$item->member_id, $item->staff_id])->pluck('name', 'id');

        return $this->ok([
            'id' => $item->id, 'topic' => $item->topic, 'with' => $names[$item->member_id === $me ? $item->staff_id : $item->member_id] ?? 'College',
            'messages' => DB::table('conversation_messages')->where('conversation_id', $item->id)->orderBy('id')->get()->map(fn ($m) => [
                'id' => $m->id, 'body' => $m->body, 'mine' => $m->sender_id === $me, 'author' => $names[$m->sender_id] ?? null,
                'created_at' => Carbon::parse($m->created_at)->toIso8601String(), 'read' => $m->read_at !== null,
            ]),
        ]);
    }

    public function sendMessage(Request $request, int $conversation, Notifier $notifier)
    {
        $item = $this->conversation($request, $conversation);
        $data = $request->validate(['body' => 'required|string|max:3000']);
        $me = $request->user();

        DB::table('conversation_messages')->insert(['conversation_id' => $item->id, 'sender_id' => $me->id, 'body' => $data['body'], 'created_at' => now(), 'updated_at' => now()]);
        DB::table('conversations')->where('id', $item->id)->update(['last_message_at' => now(), 'updated_at' => now()]);
        $notifier->send([$item->member_id === $me->id ? $item->staff_id : $item->member_id], 'message', "Message from {$me->name}", $item->topic, ['screen' => 'message', 'conversation_id' => $item->id]);

        return $this->ok(['sent' => true], 201);
    }
}
