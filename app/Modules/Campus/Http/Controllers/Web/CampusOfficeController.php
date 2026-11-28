<?php

namespace App\Modules\Campus\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Campus\Engine\Access;
use App\Modules\Campus\Services\ElectionService;
use App\Modules\Campus\Services\LibraryService;
use App\Modules\Campus\Services\Notifier;
use App\Modules\Campus\Services\ProgressService;
use App\Modules\Admissions\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Library desk, elections, student insights, messages and the audit trail. */
class CampusOfficeController extends Controller
{
    private function school(Request $request): int
    {
        return (int) ($request->user()->school_id ?: 1);
    }

    // ---- Library -----------------------------------------------------------

    public function library(Request $request, LibraryService $library)
    {
        $school = $this->school($request);
        $search = trim((string) $request->query('q'));

        return view('office.library', [
            'books' => $library->books($school, $search ?: null, 100),
            'loans' => DB::table('library_loans')->join('library_books', 'library_books.id', '=', 'library_loans.book_id')->join('users', 'users.id', '=', 'library_loans.student_id')
                ->where('library_loans.school_id', $school)->whereIn('library_loans.status', ['issued', 'reserved'])->orderBy('due_on')
                ->get(['library_loans.*', 'library_books.title', 'users.name', 'users.student_id as code']),
            'allBooks' => DB::table('library_books')->where('school_id', $school)->orderBy('title')->pluck('title', 'id'),
            'search' => $search,
        ]);
    }

    public function addBook(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:200', 'author' => 'nullable|string|max:160', 'isbn' => 'nullable|string|max:20',
            'category' => 'nullable|string|max:60', 'shelf' => 'nullable|string|max:40', 'copies' => 'required|integer|min:1|max:5000',
        ]);
        DB::table('library_books')->insert($data + ['school_id' => $this->school($request), 'created_at' => now(), 'updated_at' => now()]);

        return back()->with('ok', 'Book added to the catalogue.');
    }

    public function issueBook(Request $request, LibraryService $library)
    {
        $data = $request->validate(['book_id' => 'required|integer', 'student_code' => 'required|string|max:40', 'days' => 'required|integer|min:1|max:90']);
        $student = User::where('school_id', $this->school($request))->where('role_id', 7)->where('student_id', trim($data['student_code']))->first();
        if (!$student) {
            return back()->withErrors(['student_code' => 'No student has that ID.'])->withInput();
        }
        $library->issue($data['book_id'], $this->school($request), $student->id, $request->user()->id, $data['days']);

        return back()->with('ok', "Issued to {$student->name}.");
    }

    public function closeLoan(Request $request, int $loan, LibraryService $library)
    {
        $data = $request->validate(['outcome' => 'required|in:returned,lost,cancelled', 'charge' => 'nullable|integer|min:0|max:100000']);
        $fine = $library->close($loan, $this->school($request), $data['outcome'], $request->user()->id, (int) ($data['charge'] ?? 0));

        return back()->with('ok', $fine ? "Saved. A charge of ৳{$fine} was added to the student's fees." : 'Saved.');
    }

    // ---- Elections ---------------------------------------------------------

    private function election(Request $request, int $id): object
    {
        $election = DB::table('elections')->where('id', $id)->where('school_id', $this->school($request))->first();
        abort_unless($election, 404);

        return $election;
    }

    public function elections(Request $request, ElectionService $service)
    {
        $school = $this->school($request);

        return view('office.elections', [
            'elections' => DB::table('elections')->where('school_id', $school)->orderByDesc('opens_at')->get()->map(function ($e) use ($service) {
                $e->state = $service->state($e);
                $e->turnout = DB::table('election_voters')->where('election_id', $e->id)->count();
                // Votes are counted only after voting has closed.
                $e->candidates = $service->candidates($e, in_array($e->state, ['counting', 'published'], true), true);

                return $e;
            }),
            'classes' => DB::table('classes')->where('school_id', $school)->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function createElection(Request $request, Notifier $notifier, AuditService $audit)
    {
        $data = $request->validate([
            'title' => 'required|string|max:160', 'position' => 'required|string|max:80', 'description' => 'nullable|string|max:1000',
            'class_id' => 'nullable|integer', 'opens_at' => 'required|date|after:now', 'closes_at' => 'required|date|after:opens_at',
        ]);
        $school = $this->school($request);
        $local = fn ($v) => Carbon::parse($v, 'Asia/Dhaka')->setTimezone(config('app.timezone'));
        $id = DB::table('elections')->insertGetId(array_merge($data, [
            'school_id' => $school, 'class_id' => $data['class_id'] ?? null, 'opens_at' => $local($data['opens_at']), 'closes_at' => $local($data['closes_at']),
            'created_by' => $request->user()->id, 'created_at' => now(), 'updated_at' => now(),
        ]));
        $audit->record('election.created', (string) $id, [], ['title' => $data['title']], $request->user(), 'campus');
        $students = !empty($data['class_id']) ? $notifier->studentsIn((int) $data['class_id']) : User::where('school_id', $school)->where('role_id', 7)->where('status', 1)->pluck('id');
        $notifier->send($students, 'campus', 'Nominations open', "Nominations are open for {$data['position']}.", ['screen' => 'elections']);

        return back()->with('ok', 'Election created. Students can nominate themselves until voting opens.');
    }

    public function reviewCandidate(Request $request, int $election, int $candidate, ElectionService $service, AuditService $audit)
    {
        $record = $this->election($request, $election);
        abort_unless($service->state($record) === 'nominations', 422, 'Candidates cannot change once voting has opened.');
        $data = $request->validate(['status' => 'required|in:approved,rejected']);
        $changed = DB::table('election_candidates')->where('id', $candidate)->where('election_id', $election)->update(['status' => $data['status'], 'updated_at' => now()]);
        abort_unless($changed, 404);
        $audit->record('election.candidate', (string) $candidate, [], $data, $request->user(), 'campus');

        return back()->with('ok', 'Candidate ' . $data['status'] . '.');
    }

    public function publishElection(Request $request, int $election, ElectionService $service, AuditService $audit)
    {
        $service->publish($this->election($request, $election));
        $audit->record('election.published', (string) $election, [], [], $request->user(), 'campus');

        return back()->with('ok', 'Result published to students.');
    }

    // ---- Insights ----------------------------------------------------------

    public function insights(Request $request, ProgressService $progress)
    {
        $school = $this->school($request);

        return view('office.insights', [
            'students' => $progress->needingAttention($school),
            'counts' => [
                'Open discipline cases' => DB::table('campus_records')->where('school_id', $school)->where('type', 'discipline_case')->whereNotIn('status', ['closed', 'dismissed'])->count(),
                'Approvals waiting' => DB::table('workflow_requests')->where('school_id', $school)->where('status', 'pending')->count(),
                'Books overdue' => DB::table('library_loans')->where('school_id', $school)->where('status', 'issued')->where('due_on', '<', now()->toDateString())->count(),
                'Club members' => DB::table('campus_records')->where('school_id', $school)->where('type', 'activity_membership')->where('status', 'approved')->count(),
                'Active projects' => DB::table('campus_records')->where('school_id', $school)->where('type', 'innovation_project')->whereIn('status', ['approved', 'in_progress', 'testing'])->count(),
                'University applications' => DB::table('campus_records')->where('school_id', $school)->where('type', 'university_application')->count(),
            ],
            'sections' => $this->syllabus($school),
        ]);
    }

    /** Syllabus coverage per teacher and subject, from the topics teachers tick off. */
    private function syllabus(int $school)
    {
        return DB::table('teacher_syllabus_topics as t')
            ->leftJoin('users', 'users.id', '=', 't.teacher_id')->leftJoin('subjects', 'subjects.id', '=', 't.subject_id')->leftJoin('classes', 'classes.id', '=', 't.class_id')
            ->where('t.school_id', $school)->groupBy('t.teacher_id', 't.subject_id', 't.class_id', 'users.name', 'subjects.name', 'classes.name')
            ->orderBy('classes.name')->orderBy('subjects.name')
            ->get(['users.name as teacher', 'subjects.name as subject', 'classes.name as class', DB::raw('round(count(t.completed_at) / count(*) * 100) as percent'), DB::raw('count(*) as topics')]);
    }

    // ---- Messages ------------------------------------------------------------

    private function thread(Request $request, int $id): object
    {
        $item = DB::table('conversations')->where('id', $id)->where('staff_id', $request->user()->id)->first();
        abort_unless($item, 404);

        return $item;
    }

    public function messages(Request $request, ?int $conversation = null)
    {
        $me = $request->user()->id;
        $threads = DB::table('conversations')->where('staff_id', $me)->orderByDesc('last_message_at')->limit(100)->get();
        $open = $conversation ? $this->thread($request, $conversation) : null;
        if ($open) {
            DB::table('conversation_messages')->where('conversation_id', $open->id)->where('sender_id', '!=', $me)->whereNull('read_at')->update(['read_at' => now()]);
        }

        return view('office.messages', [
            'threads' => $threads,
            'open' => $open,
            'messages' => $open ? DB::table('conversation_messages')->where('conversation_id', $open->id)->orderBy('id')->get() : collect(),
            'names' => User::whereIn('id', $threads->pluck('member_id')->merge($threads->pluck('student_id'))->push($me))->pluck('name', 'id'),
            'unread' => DB::table('conversation_messages')->whereIn('conversation_id', $threads->pluck('id'))->where('sender_id', '!=', $me)->whereNull('read_at')->pluck('conversation_id')->countBy(),
        ]);
    }

    public function reply(Request $request, int $conversation, Notifier $notifier)
    {
        $item = $this->thread($request, $conversation);
        $data = $request->validate(['body' => 'required|string|max:3000']);
        DB::table('conversation_messages')->insert(['conversation_id' => $item->id, 'sender_id' => $request->user()->id, 'body' => $data['body'], 'created_at' => now(), 'updated_at' => now()]);
        DB::table('conversations')->where('id', $item->id)->update(['last_message_at' => now(), 'updated_at' => now()]);
        $notifier->send([$item->member_id], 'message', "Message from {$request->user()->name}", $item->topic, ['screen' => 'message', 'conversation_id' => $item->id]);

        return back();
    }

    // ---- Audit ---------------------------------------------------------------

    public function audit(Request $request)
    {
        $school = $this->school($request);
        $search = trim((string) $request->query('q'));
        // Confidential modules: others learn that something happened, not what.
        $hidden = collect(config('campus_modules.types'))->filter(fn ($t) => !empty(config("campus_modules.groups.{$t['group']}.confidential")))->keys();
        $roles = Access::roles($request->user());

        $logs = DB::table('audit_logs')->leftJoin('users', 'users.id', '=', 'audit_logs.user_id')
            ->where(fn ($q) => $q->whereNull('users.school_id')->orWhere('users.school_id', $school))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w->where('action', 'like', "%{$search}%")->orWhere('users.name', 'like', "%{$search}%")->orWhere('record_id', $search)))
            ->orderByDesc('audit_logs.created_at')->select('audit_logs.*', 'users.name as actor')->paginate(50)->withQueryString();

        $logs->getCollection()->transform(function ($log) use ($hidden, $roles) {
            $confidential = $hidden->contains(fn ($key) => str_starts_with($log->action, "{$key}."));
            if ($confidential && !array_intersect($roles, ['Safeguarding Officer', 'Counsellor'])) {
                $log->new_value = null;
                $log->old_value = null;
                $log->record_id = 'confidential';
            }

            return $log;
        });

        return view('office.audit', ['logs' => $logs, 'search' => $search]);
    }
}
