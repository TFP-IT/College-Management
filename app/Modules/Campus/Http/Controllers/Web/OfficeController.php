<?php

namespace App\Modules\Campus\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\StudentFeeManager;
use App\Models\User;
use App\Modules\Campus\Models\FeePayment;
use App\Modules\Campus\Models\ResultPublication;
use App\Modules\Campus\Models\ServiceTicket;
use App\Modules\Campus\Services\AttendanceService;
use App\Modules\Campus\Services\FinanceService;
use App\Modules\Campus\Services\Notifier;
use App\Modules\Campus\Services\OfficeReport;
use App\Modules\Campus\Services\ResultService;
use App\Modules\Campus\Support\OfficeAccess;
use App\Modules\Campus\Support\StudentContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/** The office portal: today's picture, student records, fees, result approval, announcements and help desk. */
class OfficeController extends Controller
{
    public function __construct(private FinanceService $finance, private Notifier $notifier)
    {
    }

    private function school(Request $request): int
    {
        return (int) ($request->user()->school_id ?: 1);
    }

    private function session(Request $request): ?int
    {
        return StudentContext::runningSession($this->school($request));
    }

    /** @return array<int, string> "classId-sectionId" => label */
    private function sectionOptions(int $school): array
    {
        return DB::table('sections')->join('classes', 'classes.id', '=', 'sections.class_id')
            ->where('classes.school_id', $school)->orderBy('classes.name')->orderBy('sections.name')
            ->get(['classes.id as class_id', 'sections.id as section_id', 'classes.name as class', 'sections.name as section'])
            ->mapWithKeys(fn ($r) => ["{$r->class_id}-{$r->section_id}" => "{$r->class} {$r->section}"])->all();
    }

    private function student(Request $request, int $id): User
    {
        return User::where('role_id', 7)->where('school_id', $this->school($request))->findOrFail($id);
    }

    public function dashboard(Request $request, OfficeReport $report)
    {
        if (!OfficeAccess::allows($request->user(), 'dashboard')) {
            $areas = OfficeAccess::areas($request->user());
            abort_unless($areas, 403, 'Your account cannot open the office portal.');

            return redirect()->route(in_array('approvals', $areas, true) ? 'office.approvals' : 'office.library');
        }
        $school = $this->school($request);

        return view('office.dashboard', [
            'today' => $report->today($school, $this->session($request)),
            'money' => OfficeAccess::allows($request->user(), 'fees') ? $report->money($school) : null,
            'payments' => OfficeAccess::allows($request->user(), 'fees')
                ? FeePayment::where('school_id', $school)->where('status', 'confirmed')->latest('paid_at')->latest('id')->limit(6)->get()
                : collect(),
            'names' => User::whereIn('id', FeePayment::where('school_id', $school)->latest('id')->limit(40)->pluck('student_id'))->pluck('name', 'id'),
        ]);
    }

    // ---- Students ---------------------------------------------------------

    public function students(Request $request)
    {
        $school = $this->school($request);
        $search = trim((string) $request->query('q'));
        $section = (string) $request->query('section');

        $students = User::where('users.role_id', 7)->where('users.school_id', $school)
            ->leftJoin('enrollments', 'enrollments.user_id', '=', 'users.id')
            ->leftJoin('classes', 'classes.id', '=', 'enrollments.class_id')
            ->leftJoin('sections', 'sections.id', '=', 'enrollments.section_id')
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('users.name', 'like', "%{$search}%")->orWhere('users.student_id', 'like', "%{$search}%")->orWhere('users.phone_number', 'like', "%{$search}%")))
            ->when(preg_match('/^(\d+)-(\d+)$/', $section, $m), fn ($q) => $q->where('enrollments.class_id', $m[1] ?? 0)->where('enrollments.section_id', $m[2] ?? 0))
            ->orderBy('users.name')
            ->select('users.id', 'users.name', 'users.student_id', 'users.status', 'classes.name as class_name', 'sections.name as section_name')
            ->paginate(25)->withQueryString();

        return view('office.students', ['students' => $students, 'sections' => $this->sectionOptions($school), 'search' => $search, 'section' => $section]);
    }

    public function showStudent(Request $request, int $student, AttendanceService $attendance, ResultService $results)
    {
        $model = $this->student($request, $student);
        $context = StudentContext::for($model);
        $user = $request->user();

        return view('office.student', [
            'student' => $model,
            'context' => $context,
            'guardian' => $model->parent_id ? User::find($model->parent_id) : null,
            'attendance' => $attendance->forStudent($context),
            'results' => $results->publishedFor($context),
            'admitCards' => collect($results->upcomingExams($context, 50))->whereNotNull('category_id')->unique('category_id')->values()
                ->map(fn ($e) => ['name' => DB::table('exam_categories')->where('id', $e['category_id'])->value('name') ?: $e['name'], 'url' => DocumentController::admitCardUrl($model->id, $e['category_id'])]),
            'ledger' => OfficeAccess::allows($user, 'fees') ? $this->finance->ledger($model) : null,
        ]);
    }

    // ---- Fees -------------------------------------------------------------

    public function fees(Request $request, OfficeReport $report)
    {
        $school = $this->school($request);
        $filter = $request->query('status', 'due');
        $search = trim((string) $request->query('q'));

        $rows = StudentFeeManager::where('school_id', $school)
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w->where('title', 'like', "%{$search}%")
                ->orWhereIn('student_id', User::where('name', 'like', "%{$search}%")->orWhere('student_id', 'like', "%{$search}%")->pluck('id'))))
            ->latest('id')->limit(1500)->get();
        $invoices = $this->finance->invoices($rows)->filter(fn ($i) => match ($filter) {
            'paid' => $i['due'] === 0,
            'verifying' => $i['status'] === 'verifying',
            'all' => true,
            default => $i['due'] > 0,
        })->values();

        return view('office.fees', [
            'money' => $report->money($school),
            'invoices' => $invoices->take(100),
            'matched' => $invoices->count(),
            'students' => User::whereIn('id', $invoices->take(100)->pluck('student_id'))->get(['id', 'name', 'student_id'])->keyBy('id'),
            'sections' => $this->sectionOptions($school),
            'filter' => $filter,
            'search' => $search,
        ]);
    }

    public function bill(Request $request)
    {
        $school = $this->school($request);
        $data = $request->validate([
            'section' => ['required', Rule::in(array_keys($this->sectionOptions($school)))],
            'title' => 'required|string|max:120',
            'amount' => 'required|integer|min:1|max:1000000',
            'discount' => 'nullable|integer|min:0|lt:amount',
        ]);
        $session = $this->session($request);
        abort_unless($session, 422, 'Set the running session before billing.');
        [$class, $section] = array_map('intval', explode('-', $data['section']));

        $count = $this->finance->billClass($school, $session, $class, $section, trim($data['title']), (int) $data['amount'], (int) ($data['discount'] ?? 0));
        Log::info('Class billed', ['by' => $request->user()->id, 'section' => $data['section'], 'title' => $data['title'], 'amount' => $data['amount'], 'invoices' => $count]);

        return back()->with('ok', $count
            ? "{$count} invoices created. Families have been notified."
            : 'No new invoices: every student in that section already has one with this title.');
    }

    public function collect(Request $request, int $invoice)
    {
        $row = StudentFeeManager::where('school_id', $this->school($request))->findOrFail($invoice);
        $data = $request->validate([
            'amount' => 'required|integer|min:1',
            'method' => 'required|in:cash,bank,bkash,nagad,rocket,card',
            'reference' => 'nullable|string|max:120',
        ]);

        $payment = $this->finance->record($row->id, (int) $data['amount'], $data['method'], $data['reference'] ?? null, $request->user()->id);
        Log::info('Fee collected', ['by' => $request->user()->id, 'invoice' => $row->id, 'amount' => $payment->amount, 'receipt' => $payment->receipt_no]);

        return redirect()->route('office.receipt', $payment->id)->with('ok', 'Payment recorded.');
    }

    public function receipt(Request $request, int $payment)
    {
        $record = FeePayment::where('school_id', $this->school($request))->findOrFail($payment);

        return view('office.receipt', [
            'payment' => $record,
            'invoice' => StudentFeeManager::find($record->invoice_id),
            'student' => User::find($record->student_id),
            'receiver' => $record->received_by ? User::find($record->received_by) : null,
            'school' => DB::table('schools')->where('id', $record->school_id)->first(),
        ]);
    }

    /** Public check of a printed receipt. Shows the minimum needed to confirm it. */
    public function verifyReceipt(string $receipt)
    {
        $record = FeePayment::where('receipt_no', $receipt)->firstOrFail();

        return view('office.verify', ['payment' => $record, 'studentCode' => User::whereKey($record->student_id)->value('student_id') ?: '-']);
    }

    public function export(Request $request)
    {
        $invoices = $this->finance->invoices(StudentFeeManager::where('school_id', $this->school($request))->orderBy('id')->get());
        $students = User::whereIn('id', $invoices->pluck('student_id'))->get(['id', 'name', 'student_id'])->keyBy('id');
        // A leading =, +, - or @ would be run as a formula by spreadsheet programs.
        $safe = fn ($v) => preg_match('/^[=+\-@\t\r]/', (string) $v) ? "'" . $v : $v;

        return response()->streamDownload(function () use ($invoices, $students, $safe) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Invoice', 'Student ID', 'Student', 'Title', 'Total', 'Paid', 'Due', 'Status'], ',', '"', '');
            foreach ($invoices as $i) {
                $s = $students->get($i['student_id']);
                fputcsv($out, array_map($safe, [$i['number'], $s?->student_id, $s?->name, $i['title'], $i['total'], $i['paid'], $i['due'], $i['status']]), ',', '"', '');
            }
            fclose($out);
        }, 'fees-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv']);
    }

    // ---- Results ----------------------------------------------------------

    private function publication(Request $request, int $id): ResultPublication
    {
        return ResultPublication::where('school_id', $this->school($request))->findOrFail($id);
    }

    private function labelled($publications, int $school)
    {
        $exams = DB::table('exam_categories')->where('school_id', $school)->pluck('name', 'id');
        $sections = $this->sectionOptions($school);
        $people = User::whereIn('id', $publications->pluck('submitted_by')->merge($publications->pluck('published_by'))->filter())->pluck('name', 'id');

        return $publications->each(function ($p) use ($exams, $sections, $people) {
            $p->exam_name = $exams[$p->exam_category_id] ?? 'Examination';
            $p->section_label = $sections["{$p->class_id}-{$p->section_id}"] ?? 'Section';
            $p->submitter = $people[$p->submitted_by] ?? null;
            $p->publisher = $people[$p->published_by] ?? null;
        });
    }

    public function results(Request $request)
    {
        $school = $this->school($request);
        $all = $this->labelled(ResultPublication::where('school_id', $school)->latest('submitted_at')->limit(100)->get(), $school);

        return view('office.results', [
            'waiting' => $all->where('status', 'submitted'),
            'others' => $all->where('status', '!=', 'submitted'),
            'unsent' => $this->unsentMarks($school, $this->session($request)),
        ]);
    }

    /** Marks typed into the older web screens that nobody has sent for approval yet. */
    private function unsentMarks(int $school, ?int $session)
    {
        $rows = DB::table('gradebooks as g')
            ->leftJoin('result_publications as p', function ($join) {
                $join->on('p.session_id', '=', 'g.session_id')->on('p.exam_category_id', '=', 'g.exam_category_id')
                    ->on('p.class_id', '=', 'g.class_id')->on('p.section_id', '=', 'g.section_id');
            })
            ->whereNull('p.id')->where('g.school_id', $school)
            ->when($session, fn ($q) => $q->where('g.session_id', $session))
            ->groupBy('g.session_id', 'g.exam_category_id', 'g.class_id', 'g.section_id')
            ->get(['g.session_id', 'g.exam_category_id', 'g.class_id', 'g.section_id', DB::raw('count(distinct g.student_id) as students')]);

        $exams = DB::table('exam_categories')->where('school_id', $school)->pluck('name', 'id');
        $sections = $this->sectionOptions($school);

        return $rows->each(function ($r) use ($exams, $sections) {
            $r->exam_name = $exams[$r->exam_category_id] ?? 'Examination';
            $r->section_label = $sections["{$r->class_id}-{$r->section_id}"] ?? 'Section';
        });
    }

    /** Puts web-entered marks into the approval queue so they can be checked and published. */
    public function openResult(Request $request)
    {
        $school = $this->school($request);
        $key = $request->validate([
            'session_id' => 'required|integer', 'exam_category_id' => 'required|integer',
            'class_id' => 'required|integer', 'section_id' => 'required|integer',
        ]);
        abort_unless(DB::table('gradebooks')->where($key + ['school_id' => $school])->exists(), 404);

        $publication = ResultPublication::firstOrCreate($key, ['school_id' => $school, 'status' => 'submitted', 'submitted_at' => now()]);

        return redirect()->route('office.result', $publication->id);
    }

    public function showResult(Request $request, int $publication, ResultService $results)
    {
        $record = $this->publication($request, $publication);
        $this->labelled(collect([$record]), $record->school_id);
        $sheet = collect($results->sheet($record));
        $subjects = $sheet->flatMap(fn ($r) => collect($r['subjects'])->pluck('subject', 'subject_id')->all())->all();
        $subjects = $sheet->flatMap(fn ($r) => $r['subjects'])->unique('subject_id')->sortBy('subject')->values();
        $enrolled = $this->notifier->studentsIn($record->class_id, $record->section_id)->count();
        $classSubjects = DB::table('subjects')->where('class_id', $record->class_id)->where('session_id', $record->session_id)->count();

        return view('office.result', [
            'publication' => $record,
            'sheet' => $sheet,
            'subjects' => $subjects,
            // Things a checker should look at before families see the marks.
            'warnings' => array_filter([
                $sheet->count() < $enrolled ? ($enrolled - $sheet->count()) . ' enrolled students have no marks at all.' : null,
                ($gaps = $sheet->filter(fn ($r) => count($r['subjects']) < $classSubjects)->count()) ? "{$gaps} students are missing marks in one or more of the {$classSubjects} subjects." : null,
            ]),
        ]);
    }

    public function publishResult(Request $request, int $publication)
    {
        $record = $this->publication($request, $publication);
        abort_unless($record->status === 'submitted', 422, 'Only submitted results can be published.');
        // The person who sent the marks cannot also approve them.
        abort_if((int) $record->submitted_by === (int) $request->user()->id, 403, 'Someone other than the sender must approve these results.');

        $record->update(['status' => 'published', 'published_by' => $request->user()->id, 'published_at' => now(), 'note' => null]);

        $students = DB::table('gradebooks')->where($record->only(['exam_category_id', 'class_id', 'section_id', 'session_id', 'school_id']))->pluck('student_id');
        $exam = DB::table('exam_categories')->where('id', $record->exam_category_id)->value('name');
        $this->notifier->toFamilies($students, 'result', 'Result published', "{$exam} results are ready.", ['screen' => 'results']);
        Log::info('Results published', ['by' => $request->user()->id, 'publication' => $record->id]);

        return redirect()->route('office.results')->with('ok', 'Results published. Students and guardians have been notified.');
    }

    public function returnResult(Request $request, int $publication)
    {
        $record = $this->publication($request, $publication);
        abort_unless(in_array($record->status, ['submitted', 'published'], true), 422);
        $data = $request->validate(['note' => 'required|string|max:500']);

        $record->update(['status' => 'returned', 'note' => $data['note'], 'published_by' => null, 'published_at' => null]);
        if ($record->submitted_by) {
            $this->notifier->send([$record->submitted_by], 'result', 'Results sent back', $data['note']);
        }
        Log::info('Results returned', ['by' => $request->user()->id, 'publication' => $record->id]);

        return redirect()->route('office.results')->with('ok', 'Sent back to the teacher. Families can no longer see these marks.');
    }

    // ---- Announcements ----------------------------------------------------

    public function announcements(Request $request)
    {
        $school = $this->school($request);

        return view('office.announcements', [
            'notices' => DB::table('noticeboard')->where('school_id', $school)->orderByDesc('id')->limit(20)->get(),
            'sections' => $this->sectionOptions($school),
        ]);
    }

    public function announce(Request $request)
    {
        $school = $this->school($request);
        $sections = $this->sectionOptions($school);
        $data = $request->validate([
            'title' => 'required|string|max:160',
            'body' => 'required|string|max:3000',
            'audience' => ['required', Rule::in(array_merge(['everyone', 'families', 'teachers'], array_keys($sections)))],
        ]);

        $people = fn (array $roles) => User::where('school_id', $school)->where('status', 1)->whereIn('role_id', $roles)->pluck('id');
        $sectionOnly = isset($sections[$data['audience']]);

        // The noticeboard is public to the whole college, so a message for one group is only delivered to that group.
        if ($data['audience'] === 'everyone') {
            DB::table('noticeboard')->insert([
                'notice_title' => $data['title'], 'notice' => e($data['body']), 'start_date' => (string) time(), 'start_time' => '', 'end_date' => '',
                'end_time' => '', 'status' => 1, 'show_on_website' => 0, 'image' => '', 'school_id' => $school,
                'session_id' => (int) $this->session($request), 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $payload = ['screen' => 'notices'];
        if ($sectionOnly) {
            [$class, $section] = array_map('intval', explode('-', $data['audience']));
            $sent = $this->notifier->toFamilies($this->notifier->studentsIn($class, $section), 'notice', $data['title'], $data['body'], $payload);
        } else {
            $roles = ['everyone' => [3, 6, 7], 'families' => [6, 7], 'teachers' => [3]][$data['audience']];
            $sent = $this->notifier->send($people($roles), 'notice', $data['title'], $data['body'], $payload);
        }

        return back()->with('ok', "Announcement sent to {$sent} accounts.");
    }

    // ---- Help desk --------------------------------------------------------

    public function tickets(Request $request)
    {
        $filter = $request->query('status', 'active');
        $tickets = ServiceTicket::with('requester:id,name,role_id')->where('school_id', $this->school($request))
            ->when($filter === 'active', fn ($q) => $q->whereIn('status', ['open', 'in_progress', 'waiting']))
            ->when($filter === 'done', fn ($q) => $q->whereIn('status', ['resolved', 'closed']))
            ->orderByRaw("priority = 'high' desc")->latest('updated_at')->paginate(25)->withQueryString();

        return view('office.tickets', ['tickets' => $tickets, 'filter' => $filter]);
    }

    private function findTicket(Request $request, int $id): ServiceTicket
    {
        return ServiceTicket::where('school_id', $this->school($request))->findOrFail($id);
    }

    public function showTicket(Request $request, int $ticket)
    {
        $record = $this->findTicket($request, $ticket)->load('requester', 'messages.author');

        return view('office.ticket', [
            'ticket' => $record,
            'student' => $record->student_id ? User::find($record->student_id) : null,
        ]);
    }

    public function replyTicket(Request $request, int $ticket)
    {
        $record = $this->findTicket($request, $ticket);
        $data = $request->validate([
            'message' => 'nullable|string|max:3000',
            'status' => ['required', Rule::in(ServiceTicket::STATUSES)],
        ]);

        if (!empty($data['message'])) {
            $record->messages()->create(['user_id' => $request->user()->id, 'body' => $data['message'], 'from_staff' => true]);
            $this->notifier->send([$record->requester_id], 'help', 'Reply from the college', "There is a reply to \"{$record->subject}\".", ['screen' => 'help']);
        }
        $record->update(['status' => $data['status'], 'resolved_at' => in_array($data['status'], ['resolved', 'closed'], true) ? now() : null]);
        $record->touch();

        return back()->with('ok', 'Saved.');
    }
}
