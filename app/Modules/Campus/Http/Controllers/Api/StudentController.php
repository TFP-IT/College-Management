<?php

namespace App\Modules\Campus\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use App\Models\StudentFeeManager;
use App\Models\User;
use App\Modules\Campus\Http\Controllers\Web\DocumentController;
use App\Modules\Campus\Models\Submission;
use App\Modules\Campus\Services\AttendanceService;
use App\Modules\Campus\Services\FinanceService;
use App\Modules\Campus\Services\LearningService;
use App\Modules\Campus\Services\NoticeService;
use App\Modules\Campus\Services\ResultService;
use App\Modules\Campus\Services\TimetableService;
use App\Modules\Campus\Support\StudentContext;
use App\Services\Payments\SslCommerzClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * One student's record, used by both the student and guardian apps.
 * Someone without access gets "not found", so ids cannot be probed.
 */
class StudentController extends Controller
{
    public function __construct(
        private AttendanceService $attendance,
        private TimetableService $timetables,
        private LearningService $learning,
        private ResultService $results,
        private FinanceService $finance,
    ) {
    }

    private function context(Request $request, User $student, bool $actingForStudent = false): StudentContext
    {
        $allowed = $actingForStudent
            ? StudentContext::isFamily($request->user(), $student)
            : StudentContext::canView($request->user(), $student);
        abort_unless($allowed, 404, 'Student not found.');

        return StudentContext::for($student);
    }

    /** Teachers see academic records only; money is for the family and the office. */
    private function seesFees(User $viewer, User $student): bool
    {
        return StudentContext::isFamily($viewer, $student) || in_array((int) $viewer->role_id, [1, 2, 4], true);
    }

    private function ok(array $data, int $status = 200)
    {
        return response()->json(['data' => $data], $status);
    }

    public function today(Request $request, User $student, NoticeService $notices)
    {
        $ctx = $this->context($request, $student);
        $attendance = $this->attendance->forStudent($ctx);
        $week = $ctx->enrolled() ? $this->week($ctx) : [];
        $assignments = collect($this->learning->assignments($ctx));
        $fees = $this->seesFees($request->user(), $student) ? $this->finance->ledger($student)['summary'] : null;
        $result = $this->results->publishedFor($ctx)[0] ?? null;

        return $this->ok([
            'student' => MeController::studentCard($student, $ctx),
            'date' => now(AttendanceService::TIMEZONE)->toDateString(),
            'attendance' => [
                'today' => $attendance['today'],
                'rate' => $attendance['session']['rate'],
                'present' => $attendance['session']['present'] + $attendance['session']['late'],
                'total' => $attendance['session']['total'],
            ],
            'periods' => collect($week)->firstWhere('day', TimetableService::todayKey())['periods'] ?? [],
            'assignments' => $assignments->whereIn('state', ['open', 'overdue'])->sortBy('due_at')->take(4)->values(),
            'open_assignments' => $assignments->whereIn('state', ['open', 'overdue'])->count(),
            'exams' => $this->results->upcomingExams($ctx),
            'fees' => $fees,
            'latest_result' => $result ? collect($result)->only(['exam', 'gpa', 'grade', 'passed', 'published_at']) : null,
            'notices' => array_slice($notices->latest($ctx->schoolId, 3), 0, 3),
        ]);
    }

    public function timetable(Request $request, User $student)
    {
        $ctx = $this->context($request, $student);

        return $this->ok([
            'today' => TimetableService::todayKey(),
            'days' => $ctx->enrolled() ? $this->week($ctx) : [],
        ]);
    }

    public function attendance(Request $request, User $student)
    {
        $request->validate(['month' => 'nullable|date_format:Y-m']);

        return $this->ok($this->attendance->forStudent($this->context($request, $student), $request->query('month')));
    }

    public function results(Request $request, User $student)
    {
        $ctx = $this->context($request, $student);

        $exams = $this->results->upcomingExams($ctx);
        $names = DB::table('exam_categories')->whereIn('id', array_column($exams, 'category_id'))->pluck('name', 'id');

        return $this->ok([
            'results' => array_map(fn ($r) => $r + ['marksheet_url' => DocumentController::marksheetUrl($student->id, $r['id'])], $this->results->publishedFor($ctx)),
            'exams' => $exams,
            'admit_cards' => collect($exams)->whereNotNull('category_id')->unique('category_id')->values()->map(fn ($e) => [
                'category_id' => $e['category_id'], 'exam' => $names[$e['category_id']] ?? $e['name'], 'url' => DocumentController::admitCardUrl($student->id, $e['category_id']),
            ]),
        ]);
    }

    public function subjects(Request $request, User $student)
    {
        return $this->ok(['subjects' => $this->learning->subjects($this->context($request, $student))]);
    }

    public function lessons(Request $request, User $student, int $subject)
    {
        $ctx = $this->context($request, $student);
        $lessons = $this->learning->lessonQuery($ctx)->where('subject_id', $subject)->orderBy('chapter')->orderBy('published_at')->get();
        $done = DB::table('lms_lesson_progress')->where('student_id', $student->id)->whereIn('lesson_id', $lessons->pluck('id'))->pluck('lesson_id')->flip();

        return $this->ok([
            'subject' => DB::table('subjects')->where('id', $subject)->value('name'),
            'lessons' => $lessons->map(fn ($l) => [
                'id' => $l->id,
                'chapter' => $l->chapter,
                'title' => $l->title,
                'summary' => $l->summary,
                'content' => $l->content,
                'link_url' => $l->link_url,
                'published_at' => $l->published_at?->toIso8601String(),
                'completed' => $done->has($l->id),
            ])->values(),
        ]);
    }

    public function completeLesson(Request $request, User $student, int $lesson)
    {
        $ctx = $this->context($request, $student, true);
        $this->learning->lessonQuery($ctx)->findOrFail($lesson);

        DB::table('lms_lesson_progress')->updateOrInsert(
            ['lesson_id' => $lesson, 'student_id' => $student->id],
            ['completed_at' => now()],
        );

        return $this->ok(['completed' => true]);
    }

    public function assignments(Request $request, User $student)
    {
        return $this->ok(['assignments' => $this->learning->assignments($this->context($request, $student))]);
    }

    public function submit(Request $request, User $student, int $assignment)
    {
        $ctx = $this->context($request, $student, true);
        $item = $this->learning->assignmentQuery($ctx)->findOrFail($assignment);
        $existing = Submission::where('assignment_id', $item->id)->where('student_id', $student->id)->first();

        abort_if($existing?->graded_at !== null, 422, 'This work has already been marked.');
        abort_if(!$item->allow_late && $item->due_at->isPast(), 422, 'The deadline has passed.');

        $data = $request->validate([
            'body' => 'nullable|string|max:5000|required_without:attachment',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
        ], ['body.required_without' => 'Write your answer or attach a photo of your work.']);

        $values = ['body' => $data['body'] ?? null, 'submitted_at' => now(), 'is_late' => $item->due_at->isPast()];
        if ($request->hasFile('attachment')) {
            $values['attachment_path'] = $request->file('attachment')->store("submissions/{$item->id}", 'local');
        }

        $submission = Submission::updateOrCreate(['assignment_id' => $item->id, 'student_id' => $student->id], $values);
        $this->learning->refreshCounts($item);

        return $this->ok(['submission_id' => $submission->id, 'is_late' => $submission->is_late], 201);
    }

    public function fees(Request $request, User $student)
    {
        $this->context($request, $student);
        abort_unless($this->seesFees($request->user(), $student), 404, 'Student not found.');

        return $this->ok($this->finance->ledger($student) + [
            'online_payment' => app(SslCommerzClient::class)->isConfigured(),
            'can_pay' => StudentContext::isFamily($request->user(), $student),
        ]);
    }

    /** Starts an online payment for what is still due on one invoice. The amount comes from the ledger. */
    public function pay(Request $request, User $student, int $invoice, SslCommerzClient $gateway)
    {
        $this->context($request, $student, true);
        $row = StudentFeeManager::where('id', $invoice)->where('student_id', $student->id)->firstOrFail();
        $summary = $this->finance->invoices(collect([$row]))->first();

        abort_if($summary['due'] < 1, 422, 'This invoice is already paid.');
        abort_if($summary['status'] === 'verifying', 422, 'The office is checking a payment for this invoice.');
        abort_unless($gateway->isConfigured(), 503, 'Online payment is not available right now. Please pay at the accounts office.');

        $transaction = PaymentTransaction::create([
            'payable_type' => StudentFeeManager::class,
            'payable_id' => $row->id,
            'user_id' => $student->id,
            'type' => 'monthly_tuition',
            'amount' => $summary['due'],
            'currency' => 'BDT',
            'transaction_id' => 'MCC_FEE_' . Str::lower(Str::random(20)),
            'student_id' => $student->student_id,
            'student_name' => $student->name,
            'status' => 'pending',
        ]);

        $root = $gateway->isSandbox() ? rtrim(url('/'), '/') : rtrim((string) config('services.sslcommerz.callback_url'), '/');
        $payer = $request->user();

        try {
            $result = $gateway->createSession([
                'total_amount' => $summary['due'],
                'currency' => 'BDT',
                'tran_id' => $transaction->transaction_id,
                'success_url' => $root . '/api/payment/success',
                'fail_url' => $root . '/api/payment/fail',
                'cancel_url' => $root . '/api/payment/cancel',
                'ipn_url' => $root . '/api/payment/ipn',
                'cus_name' => $payer->name,
                'cus_email' => $payer->email,
                'cus_add1' => 'Dhaka',
                'cus_city' => 'Dhaka',
                'cus_postcode' => '1000',
                'cus_country' => 'Bangladesh',
                'cus_phone' => $payer->phone_number ?: '01700000000',
                'shipping_method' => 'NO',
                'num_of_item' => 1,
                'product_name' => Str::limit($row->title, 90),
                'product_category' => 'Education',
                'product_profile' => 'general',
            ])->json();
        } catch (ConnectionException $e) {
            $transaction->update(['status' => 'failed']);
            abort(504, 'The payment gateway did not respond. Please try again.');
        }

        if (($result['status'] ?? null) !== 'SUCCESS') {
            $transaction->update(['status' => 'failed']);
            Log::warning('SSLCommerz refused a fee payment session', ['invoice' => $row->id, 'reason' => $result['failedreason'] ?? null]);
            abort(502, 'The payment could not be started. Please try again.');
        }

        return $this->ok(['gateway_url' => $result['GatewayPageURL'], 'amount' => $summary['due']]);
    }

    private function week(StudentContext $ctx): array
    {
        return $this->timetables->week([
            'class_id' => $ctx->classId,
            'section_id' => $ctx->sectionId,
            'school_id' => $ctx->schoolId,
            'session_id' => $ctx->sessionId,
        ]);
    }
}
