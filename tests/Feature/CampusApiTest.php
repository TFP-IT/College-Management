<?php

namespace Tests\Feature;

use App\Models\PaymentTransaction;
use App\Models\StudentFeeManager;
use App\Models\User;
use App\Modules\Campus\Models\Assignment;
use App\Modules\Campus\Models\CampusNotification;
use App\Modules\Campus\Models\FeePayment;
use App\Modules\Campus\Models\ResultPublication;
use App\Modules\Campus\Models\ServiceTicket;
use App\Modules\Campus\Services\FinanceService;
use App\Modules\Campus\Services\ResultService;
use Database\Seeders\CampusDemoSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CampusApiTest extends TestCase
{
    use DatabaseTransactions;

    private User $student;
    private User $other;
    private User $guardian;
    private User $teacher;
    private User $office;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seed(CampusDemoSeeder::class);
        $this->student = User::where('email', 'nusrat@demo.mcc.test')->firstOrFail();
        $this->other = User::where('email', 'sadia@demo.mcc.test')->firstOrFail();
        $this->guardian = User::where('email', 'guardian@demo.mcc.test')->firstOrFail();
        $this->teacher = User::where('email', 'farhana@demo.mcc.test')->firstOrFail();
        $this->office = User::where('email', 'office@demo.mcc.test')->firstOrFail();
    }

    private function as(User $user): static
    {
        return $this->withToken($user->createToken('t')->plainTextToken);
    }

    /** A browser session. API calls earlier in a test leave their token and guard behind, so both are reset. */
    private function web(User $user): static
    {
        $this->flushHeaders();
        $this->app['auth']->forgetGuards();
        $this->app['auth']->shouldUse('web');

        return $this->actingAs($user, 'web');
    }

    private function url(string $path, ?User $student = null): string
    {
        return '/api/v1/campus/students/' . ($student ?? $this->student)->id . '/' . $path;
    }

    public function test_the_demo_school_refuses_to_run_in_production(): void
    {
        $before = DB::table('classes')->count();
        $this->app['env'] = 'production';

        (new CampusDemoSeeder())->run();

        $this->assertSame($before, DB::table('classes')->count());
    }

    public function test_student_and_guardian_read_the_same_real_record(): void
    {
        foreach ([$this->student, $this->guardian] as $viewer) {
            $this->as($viewer)->getJson($this->url('today'))->assertOk()
                ->assertJsonPath('data.student.code', $this->student->student_id)
                ->assertJsonPath('data.fees.due', 2750);
        }
        foreach (['timetable', 'attendance', 'results', 'subjects', 'assignments', 'fees'] as $path) {
            $this->as($this->student)->getJson($this->url($path))->assertOk();
        }
        $this->as($this->guardian)->getJson('/api/v1/campus/me')->assertOk()->assertJsonCount(2, 'data.children');
    }

    public function test_nobody_else_can_open_a_students_record(): void
    {
        foreach ([$this->student, $this->guardian] as $viewer) {
            $this->as($viewer)->getJson($this->url('today', $this->other))->assertNotFound();
            $this->as($viewer)->getJson($this->url('fees', $this->other))->assertNotFound();
        }
        $this->app['auth']->forgetGuards();
        $this->flushHeaders()->getJson($this->url('today'))->assertUnauthorized();
    }

    public function test_a_class_teacher_sees_academics_but_never_money(): void
    {
        $this->as($this->teacher)->getJson($this->url('today'))->assertOk()->assertJsonPath('data.fees', null);
        $this->as($this->teacher)->getJson($this->url('attendance'))->assertOk();
        $this->as($this->teacher)->getJson($this->url('fees'))->assertNotFound();
        $this->as($this->teacher)->postJson($this->url('lessons/1/complete'))->assertNotFound();
    }

    public function test_marks_stay_hidden_until_the_office_publishes_them(): void
    {
        $results = fn () => collect($this->as($this->student)->getJson($this->url('results'))->json('data.results'))->pluck('exam');
        $this->assertEquals(['First term examination'], $results()->all());

        $publication = ResultPublication::where('status', 'submitted')->where('school_id', 1)->latest('id')->firstOrFail();
        $this->web($this->office)->post(route('office.result.publish', $publication->id))->assertRedirect();
        $this->app['auth']->forgetGuards();

        $this->assertCount(2, $results());
        $this->assertTrue(CampusNotification::where('user_id', $this->guardian->id)->where('category', 'result')->exists());

        $this->web($this->office)->post(route('office.result.return', $publication->id), ['note' => 'Check Physics'])->assertRedirect();
        $this->app['auth']->forgetGuards();
        $this->assertCount(1, $results());
    }

    public function test_one_failed_subject_fails_the_exam(): void
    {
        $service = app(ResultService::class);
        $names = collect([1 => 'A', 2 => 'B']);

        $this->assertSame(0.0, $service->compute(1, [1 => 95, 2 => 20], $names)['gpa']);
        $this->assertSame(4.5, $service->compute(1, [1 => 85, 2 => 74], $names)['gpa']);
        // A fractional mark between two bands belongs to the lower band.
        $this->assertSame('A', $service->grade(1, 79.9)['name']);
    }

    public function test_a_teacher_cannot_edit_marks_once_sent_to_the_office(): void
    {
        $publication = ResultPublication::where('status', 'submitted')->latest('id')->firstOrFail();
        $subject = DB::table('subjects')->where('name', 'Physics')->where('class_id', $publication->class_id)->value('id');
        $body = $publication->only(['exam_category_id', 'class_id', 'section_id'])
            + ['subject_id' => $subject, 'marks' => [['student_id' => $this->student->id, 'mark' => 99]]];

        $this->as($this->teacher)->postJson('/api/v1/campus/teaching/marks', $body)->assertStatus(423);

        $publication->update(['status' => 'returned']);
        $this->as($this->teacher)->postJson('/api/v1/campus/teaching/marks', $body)->assertOk();
        $this->as($this->teacher)->postJson('/api/v1/campus/teaching/results/submit', $publication->only(['exam_category_id', 'class_id', 'section_id']))->assertOk();
        $this->assertSame('submitted', $publication->fresh()->status);
    }

    public function test_marks_entered_on_the_website_can_be_reviewed_and_published(): void
    {
        $publication = ResultPublication::where('status', 'submitted')->where('school_id', 1)->latest('id')->firstOrFail();
        $key = $publication->only(['session_id', 'exam_category_id', 'class_id', 'section_id']);
        $publication->delete();

        $this->web($this->office)->get('/office/results')->assertOk()->assertSee('not sent yet');
        $this->web($this->office)->post(route('office.result.open'), $key)->assertRedirect();
        $opened = ResultPublication::where($key)->firstOrFail();
        $this->web($this->office)->post(route('office.result.publish', $opened->id))->assertRedirect();

        $this->assertSame('published', $opened->fresh()->status);
    }

    public function test_published_marks_cannot_be_changed_from_the_older_web_screens(): void
    {
        $publication = ResultPublication::where('status', 'published')->where('school_id', 1)->latest('id')->firstOrFail();
        $subject = DB::table('subjects')->where('class_id', $publication->class_id)->value('id');

        $this->web($this->office)->post('/mark/update', $publication->only(['exam_category_id', 'class_id', 'section_id', 'session_id'])
            + ['student_id' => $this->student->id, 'subject_id' => $subject, 'mark' => 100])->assertStatus(423);
    }

    public function test_teaching_tools_are_limited_to_assigned_classes(): void
    {
        $this->as($this->student)->getJson('/api/v1/campus/teaching/today')->assertForbidden();

        $this->as($this->teacher)->postJson('/api/v1/campus/teaching/assignments', [
            'class_id' => 999, 'section_id' => 999, 'subject_id' => 999, 'title' => 'x', 'total_marks' => 10, 'due_at' => now()->addDay()->toIso8601String(),
        ])->assertForbidden();
    }

    public function test_assignment_round_trip_from_teacher_to_student_and_back(): void
    {
        $class = $this->as($this->teacher)->getJson('/api/v1/campus/teaching/classes')->json('data.classes.0');
        $created = $this->as($this->teacher)->postJson('/api/v1/campus/teaching/assignments', [
            'class_id' => $class['class_id'], 'section_id' => $class['section_id'], 'subject_id' => $class['subject_id'],
            'title' => 'Round trip', 'total_marks' => 10, 'due_at' => now()->addDays(2)->toIso8601String(),
        ])->assertCreated()->json('data.id');

        $this->assertTrue(CampusNotification::where('user_id', $this->student->id)->where('title', 'New assignment')->exists());

        $this->as($this->student)->postJson($this->url("assignments/{$created}/submit"), [])->assertStatus(422);
        $this->as($this->student)->post($this->url("assignments/{$created}/submit"), ['attachment' => UploadedFile::fake()->image('work.jpg')], ['Accept' => 'application/json'])->assertCreated();

        $work = collect($this->as($this->teacher)->getJson("/api/v1/campus/teaching/assignments/{$created}/submissions")->json('data.students'))
            ->firstWhere('id', $this->student->id)['submission'];
        $this->flushHeaders()->get($work['attachment_url'])->assertOk();
        $this->get(strtok($work['attachment_url'], '?'))->assertNotFound();

        $this->as($this->teacher)->postJson("/api/v1/campus/teaching/submissions/{$work['id']}/grade", ['marks' => 11])->assertStatus(422);
        $this->as($this->teacher)->postJson("/api/v1/campus/teaching/submissions/{$work['id']}/grade", ['marks' => 9, 'feedback' => 'Good'])->assertOk();

        $mine = collect($this->as($this->student)->getJson($this->url('assignments'))->json('data.assignments'))->firstWhere('id', $created);
        $this->assertSame('graded', $mine['state']);
        $this->assertFalse($mine['can_submit']);
        $this->as($this->student)->postJson($this->url("assignments/{$created}/submit"), ['body' => 'again'])->assertStatus(422);
        $this->assertSame(1, (int) Assignment::find($created)->marked_count);
    }

    public function test_absence_marked_by_the_teacher_reaches_the_guardian_once(): void
    {
        $class = $this->as($this->teacher)->getJson('/api/v1/campus/teaching/classes')->json('data.classes.0');
        $body = ['class_id' => $class['class_id'], 'section_id' => $class['section_id'], 'date' => now('Asia/Dhaka')->toDateString(),
            'records' => [['student_id' => $this->student->id, 'status' => 'absent', 'note' => 'Sick']]];
        $count = fn () => CampusNotification::where('user_id', $this->guardian->id)->where('category', 'attendance')->count();

        $this->as($this->teacher)->postJson('/api/teacher/attendance/submit', $body)->assertOk();
        $this->as($this->teacher)->postJson('/api/teacher/attendance/submit', $body)->assertOk();

        $this->assertSame(1, $count());
    }

    public function test_payments_update_one_ledger_and_cannot_exceed_what_is_due(): void
    {
        $invoice = StudentFeeManager::where('student_id', $this->student->id)->where('title', 'Laboratory fee')->firstOrFail();
        $collect = fn (int $amount) => $this->web($this->office)->post(route('office.fees.collect', $invoice->id), ['amount' => $amount, 'method' => 'cash']);

        $collect(501)->assertSessionHasErrors('amount');
        $collect(500)->assertRedirect();
        $collect(1)->assertSessionHasErrors('amount');

        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(800, (int) FeePayment::where('invoice_id', $invoice->id)->sum('amount'));
        $this->app['auth']->forgetGuards();
        $this->as($this->guardian)->getJson($this->url('fees'))->assertJsonPath('data.summary.due', 2250);
    }

    public function test_online_fee_payment_settles_into_the_ledger_exactly_once(): void
    {
        config(['services.sslcommerz.store_id' => 's', 'services.sslcommerz.store_password' => 'p', 'services.sslcommerz.sandbox' => true]);
        $gateway = [];
        Http::fake([
            '*gwprocess*' => Http::response(['status' => 'SUCCESS', 'GatewayPageURL' => 'https://gateway.test/pay']),
            '*validationserverAPI*' => function () use (&$gateway) { return Http::response($gateway); },
        ]);
        $invoice = StudentFeeManager::where('student_id', $this->student->id)->where('title', 'Laboratory fee')->firstOrFail();

        $this->as($this->teacher)->postJson($this->url("invoices/{$invoice->id}/pay"))->assertNotFound();
        $this->as($this->guardian)->postJson($this->url("invoices/{$invoice->id}/pay"), ['amount' => 1])->assertOk()->assertJsonPath('data.amount', 500);

        $transaction = PaymentTransaction::where('payable_type', StudentFeeManager::class)->where('payable_id', $invoice->id)->firstOrFail();
        $gateway = ['status' => 'VALID', 'tran_id' => $transaction->transaction_id, 'amount' => '500.00', 'currency_type' => 'BDT', 'card_type' => 'NAGAD'];
        $this->post('/api/payment/ipn', ['tran_id' => $transaction->transaction_id, 'val_id' => 'v'])->assertSee('OK');
        $this->post('/api/payment/success', ['tran_id' => $transaction->transaction_id, 'val_id' => 'v'])->assertRedirectContains('success');

        $this->assertSame(1, FeePayment::where('invoice_id', $invoice->id)->where('method', 'online')->count());
        $this->assertSame('paid', $invoice->fresh()->status);
    }

    public function test_a_declined_offline_payment_does_not_count_as_money_received(): void
    {
        $finance = app(FinanceService::class);

        $this->assertSame(0, $finance->paidAmount((object) ['status' => 'unpaid', 'total_amount' => 500, 'paid_amount' => 500], 0));
        $this->assertSame(500, $finance->paidAmount((object) ['status' => 'paid', 'total_amount' => 500, 'paid_amount' => 0], 0));
        $this->assertSame(200, $finance->paidAmount((object) ['status' => 'unpaid', 'total_amount' => 500, 'paid_amount' => 200], 0));
    }

    public function test_billing_a_section_twice_does_not_duplicate_invoices(): void
    {
        $key = DB::table('sections')->join('classes', 'classes.id', '=', 'sections.class_id')->where('classes.school_id', 1)->orderByDesc('sections.id')->first(['classes.id as c', 'sections.id as s']);
        $send = fn () => $this->web($this->office)->post(route('office.fees.bill'), ['section' => "{$key->c}-{$key->s}", 'title' => 'Study tour', 'amount' => 1000]);

        $send()->assertRedirect();
        $send()->assertRedirect();

        $this->assertSame(12, StudentFeeManager::where('title', 'Study tour')->count());
    }

    public function test_help_requests_are_private_and_staff_replies_notify(): void
    {
        $id = $this->as($this->student)->postJson('/api/v1/campus/tickets', ['category' => 'welfare', 'subject' => 'Private', 'message' => 'Please talk to me'])
            ->assertCreated()->assertJsonPath('data.priority', 'high')->json('data.id');

        $this->as($this->guardian)->getJson("/api/v1/campus/tickets/{$id}")->assertNotFound();
        $this->as($this->student)->postJson('/api/v1/campus/tickets', ['category' => 'general', 'subject' => 's', 'message' => 'm', 'student_id' => $this->other->id])->assertNotFound();

        $this->web($this->office)->post(route('office.ticket.reply', $id), ['message' => 'We will.', 'status' => 'in_progress'])->assertRedirect();
        $this->assertSame('in_progress', ServiceTicket::find($id)->status);
        $this->assertTrue(CampusNotification::where('user_id', $this->student->id)->where('category', 'help')->exists());
    }

    public function test_office_pages_open_only_for_the_right_roles(): void
    {
        $accounts = User::where('email', 'accounts@demo.mcc.test')->firstOrFail();
        $publication = ResultPublication::where('status', 'submitted')->latest('id')->firstOrFail();
        $payment = FeePayment::firstOrFail();
        $ticket = ServiceTicket::firstOrFail();

        foreach (['/office', '/office/students', "/office/students/{$this->student->id}", '/office/fees', '/office/fees/export', "/office/receipts/{$payment->id}",
            '/office/results', "/office/results/{$publication->id}", '/office/announcements', '/office/help', "/office/help/{$ticket->id}"] as $url) {
            $this->web($this->office)->get($url)->assertOk();
        }
        $this->web($accounts)->get('/office/fees')->assertOk();
        $this->web($accounts)->get('/office/results')->assertForbidden();
        $this->web($accounts)->post(route('office.result.publish', $publication->id))->assertForbidden();
        foreach ([$this->student, $this->guardian, $this->teacher] as $user) {
            $this->web($user)->get('/office')->assertForbidden();
        }
    }

    public function test_receipt_check_needs_the_signed_link(): void
    {
        $payment = FeePayment::firstOrFail();

        $this->get('/verify/receipt/' . $payment->receipt_no)->assertForbidden();
        $this->get(\Illuminate\Support\Facades\URL::signedRoute('receipt.verify', ['receipt' => $payment->receipt_no], null, false))
            ->assertOk()->assertSee('Genuine receipt')->assertDontSee($this->student->name);
    }

    public function test_a_section_announcement_reaches_only_that_section(): void
    {
        $key = DB::table('sections')->join('classes', 'classes.id', '=', 'sections.class_id')->where('classes.school_id', 1)->orderByDesc('sections.id')->first(['classes.id as c', 'sections.id as s']);
        $board = DB::table('noticeboard')->count();

        $this->web($this->office)->post(route('office.announce'), ['title' => 'Section only', 'body' => 'Hello', 'audience' => "{$key->c}-{$key->s}"])->assertRedirect();

        $this->assertSame($board, DB::table('noticeboard')->count());
        $this->assertSame(0, CampusNotification::where('title', 'like', '%Section only%')->where('user_id', $this->teacher->id)->count());
        $this->assertSame(1, CampusNotification::where('title', 'Section only')->where('user_id', $this->student->id)->count());
    }
}
