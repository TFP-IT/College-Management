<?php

namespace Tests\Feature;

use App\Models\StudentFeeManager;
use App\Models\User;
use App\Modules\Campus\Engine\Module;
use App\Modules\Campus\Engine\Workflow;
use App\Modules\Campus\Services\FinanceService;
use App\Modules\Campus\Services\QuizService;
use Database\Seeders\CampusDemoSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CampusModulesTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CampusDemoSeeder::class);
    }

    private function who(string $name): User
    {
        return User::where('email', "{$name}@demo.mcc.test")->firstOrFail();
    }

    private function api(User $user): static
    {
        $this->flushHeaders();
        $this->app['auth']->forgetGuards();

        return $this->withToken($user->createToken('t')->plainTextToken);
    }

    private function web(User $user): static
    {
        $this->flushHeaders();
        $this->app['auth']->forgetGuards();
        $this->app['auth']->shouldUse('web');

        return $this->actingAs($user, 'web');
    }

    private function record(string $type, array $where = []): object
    {
        return DB::table('campus_records')->where('type', $type)->where($where)->orderByDesc('id')->firstOrFail();
    }

    private function approval(string $type, int $recordId): object
    {
        return DB::table('workflow_requests')->where('subject_type', $type)->where('subject_id', $recordId)->orderByDesc('id')->firstOrFail();
    }

    private function decide(User $user, object $approval, string $decision = 'approve', ?string $note = null)
    {
        return $this->web($user)->post(route('office.approvals.decide', $approval->id), compact('decision', 'note'));
    }

    private function due(User $student): int
    {
        return app(FinanceService::class)->ledger($student)['summary']['due'];
    }

    public function test_every_module_page_opens_for_a_role_that_manages_it(): void
    {
        $admin = $this->who('office');
        foreach (Module::all() as $key => $module) {
            $manager = $module->group()['label'] === 'Safeguarding (confidential)' ? $this->who('safeguarding')
                : (str_starts_with($module->group()['label'], 'Counselling') ? $this->who('counsellor') : $admin);
            $this->web($manager)->get("/office/m/{$key}")->assertOk();
            $this->web($manager)->get("/office/m/{$key}/new")->assertOk();
            if (!$module->table() && ($id = DB::table('campus_records')->where('type', $key)->value('id'))) {
                $this->web($manager)->get("/office/m/{$key}/{$id}")->assertOk();
            }
        }
        foreach (['modules', 'approvals', 'library', 'elections', 'insights', 'audit', 'messages'] as $page) {
            $this->web($admin)->get("/office/{$page}")->assertOk();
        }
    }

    public function test_every_module_declares_a_consistent_definition(): void
    {
        foreach (Module::all() as $key => $module) {
            $this->assertNotNull($module->group(), $key);
            $this->assertArrayHasKey($module->initialStatus(), $module->get('statuses'), $key);
            foreach (array_merge($module->get('family.create', []), $module->get('teacher.create', [])) as $field) {
                $this->assertTrue($field === 'staff_id' || array_key_exists($field, $module->fields()), "{$key}.{$field}");
            }
            if ($join = $module->get('join')) {
                $this->assertNotNull(config("campus_modules.types.{$join['type']}"), $key);
            }
            if ($module->table()) {
                foreach (array_keys($module->fields()) as $column) {
                    $this->assertTrue(Schema::hasColumn($module->table(), $column), "{$key}.{$column}");
                }
            }
        }
    }

    public function test_confidential_records_are_closed_to_administrators_and_other_people(): void
    {
        $concern = $this->record('safeguarding_concern');

        $this->web($this->who('office'))->get('/office/m/safeguarding_concern')->assertForbidden();
        $this->web($this->who('principal'))->get("/office/m/safeguarding_concern/{$concern->id}")->assertForbidden();
        $this->web($this->who('safeguarding'))->get("/office/m/safeguarding_concern/{$concern->id}")->assertOk();
        $this->assertTrue(DB::table('audit_logs')->where('action', 'safeguarding_concern.viewed')->exists());
        // Another family member cannot see a concern someone else raised.
        $this->api($this->who('tahmid'))->getJson("/api/v1/campus/records/safeguarding_concern/{$concern->id}?student_id={$concern->student_id}")->assertNotFound();
        $this->api($this->who('office'))->getJson('/api/v1/campus/records/health_record')->assertNotFound();
    }

    public function test_discipline_reaches_the_family_only_when_the_office_informs_them(): void
    {
        $case = $this->record('discipline_case');
        $student = User::findOrFail($case->student_id);
        $list = fn () => $this->api($student)->getJson('/api/v1/campus/records/discipline_case')->assertOk()->json('data.records');

        $this->assertCount(0, $list());
        $this->web($this->who('discipline'))->post("/office/m/discipline_case/{$case->id}", ['status' => 'guardian_informed'])->assertRedirect();
        $this->assertCount(1, $list());
        $this->api($student)->postJson('/api/v1/campus/records/discipline_case', ['title' => 'x'])->assertForbidden();
    }

    public function test_a_waiver_needs_two_different_people_and_then_changes_the_ledger(): void
    {
        $student = $this->who('nusrat');
        $invoice = StudentFeeManager::where('student_id', $student->id)->where('title', 'Laboratory fee')->firstOrFail();
        $before = $this->due($student);

        $id = $this->api($this->who('guardian'))->postJson('/api/v1/campus/records/fee_waiver', [
            'student_id' => $student->id, 'title' => 'Lab fee', 'invoice' => 'INV-' . $invoice->id, 'amount' => 200, 'reason' => 'Family hardship',
        ])->assertCreated()->json('data.id');
        $approval = $this->approval('fee_waiver', $id);

        // The principal is second, so cannot go first.
        $this->decide($this->who('principal'), $approval)->assertForbidden();
        $this->decide($this->who('accounts'), $approval)->assertRedirect();
        // The same person cannot also take the second step, and an edit cannot skip it.
        $this->decide($this->who('accounts'), $approval)->assertForbidden();
        $this->web($this->who('office'))->post("/office/m/fee_waiver/{$id}", ['status' => 'approved'])->assertStatus(422);
        $this->assertSame($before, $this->due($student));

        $this->decide($this->who('principal'), $approval)->assertRedirect();

        $this->assertSame($before - 200, $this->due($student));
        $this->assertSame('approved', $this->record('fee_waiver')->status);
        $this->assertSame(-200, (int) DB::table('fee_adjustments')->where('invoice_id', $invoice->id)->value('amount'));
    }

    public function test_a_waiver_larger_than_the_balance_stays_waiting(): void
    {
        $student = $this->who('nusrat');
        $invoice = StudentFeeManager::where('student_id', $student->id)->where('title', 'Laboratory fee')->firstOrFail();
        $id = $this->api($student)->postJson('/api/v1/campus/records/fee_waiver', ['title' => 'x', 'invoice' => (string) $invoice->id, 'amount' => 99999, 'reason' => 'x'])->json('data.id');
        $approval = $this->approval('fee_waiver', $id);

        $this->decide($this->who('accounts'), $approval)->assertRedirect();
        $this->decide($this->who('principal'), $approval)->assertSessionHasErrors('note');

        $this->assertSame('pending', DB::table('workflow_requests')->find($approval->id)->status);
        $this->assertSame('pending', $this->record('fee_waiver')->status);
    }

    public function test_an_approved_scholarship_discounts_open_and_future_tuition_once(): void
    {
        $student = $this->who('nusrat');
        $application = $this->record('scholarship_application', ['student_id' => $student->id]);
        $approval = $this->approval('scholarship_application', $application->id);
        $before = $this->due($student);

        $this->decide($this->who('accounts'), $approval)->assertRedirect();
        $this->decide($this->who('principal'), $approval)->assertRedirect();

        // 25% of the one unpaid tuition invoice (2,250).
        $this->assertSame($before - 563, $this->due($student));

        $ctx = DB::table('enrollments')->where('user_id', $student->id)->first();
        app(FinanceService::class)->billClass(1, (int) $ctx->session_id, (int) $ctx->class_id, (int) $ctx->section_id, 'Tuition, Next month', 2000);
        $this->assertSame($before - 563 + 1500, $this->due($student));
    }

    public function test_monthly_billing_is_safe_to_run_twice_and_adds_one_late_fee(): void
    {
        $count = fn () => StudentFeeManager::where('title', 'Tuition, ' . now('Asia/Dhaka')->addMonth()->format('F Y'))->count();
        $this->travelTo(now()->addMonth()->startOfMonth()->addDay());

        $this->artisan('campus:bill-fees')->assertSuccessful();
        $this->artisan('campus:bill-fees')->assertSuccessful();
        $this->assertSame(12, StudentFeeManager::where('title', 'Tuition, ' . now('Asia/Dhaka')->format('F Y'))->count());

        $this->travelTo(now()->startOfMonth()->addDays(20));
        $this->artisan('campus:bill-fees')->assertSuccessful();
        $this->artisan('campus:bill-fees')->assertSuccessful();
        $this->assertSame(12, DB::table('fee_adjustments')->where('kind', 'late_fee')->count());
        $this->travelBack();
    }

    public function test_joining_respects_places_and_prevents_duplicates(): void
    {
        $route = $this->record('transport_route');
        $student = $this->who('sadia');
        $join = fn () => $this->api($student)->postJson("/api/v1/campus/records/transport_route/{$route->id}/join", ['stop' => 'Bank Town']);

        $join()->assertCreated()->assertJsonPath('data.status', 'pending');
        $join()->assertStatus(422);

        DB::table('campus_records')->where('id', $route->id)->update(['data' => json_encode(['seats' => 1, 'stops' => 'x', 'vehicle' => 'x'])]);
        DB::table('campus_records')->where('type', 'transport_application')->where('parent_id', $route->id)->orderBy('id')->limit(1)->update(['status' => 'approved']);
        $this->api($this->who('lamia'))->postJson("/api/v1/campus/records/transport_route/{$route->id}/join", ['stop' => 'x'])->assertStatus(422);
    }

    public function test_leave_approval_reaches_the_staff_record(): void
    {
        $leave = $this->record('leave_request');
        $approval = $this->approval('leave_request', $leave->id);

        $this->decide($this->who('hr'), $approval)->assertRedirect();
        $this->decide($this->who('principal'), $approval)->assertRedirect();

        $this->assertSame(2.0, (float) DB::table('staff_leaves')->where('user_id', $leave->staff_id)->orderByDesc('id')->value('days'));
        $this->api($this->who('farhana'))->getJson('/api/v1/campus/records/payslip')->assertOk()->assertJsonCount(1, 'data.records');
        $this->api($this->who('guardian'))->getJson('/api/v1/campus/records/payslip')->assertNotFound();
    }

    public function test_graduation_cannot_be_approved_while_something_is_outstanding(): void
    {
        $student = $this->who('nusrat');
        $this->web($this->who('office'))->post('/office/m/graduation_clearance', [
            'student_id' => $student->id, 'title' => 'HSC Preparation', 'academic' => 'complete', 'property' => 'complete', 'documents' => 'complete',
        ])->assertRedirect();
        $approval = $this->approval('graduation_clearance', $this->record('graduation_clearance')->id);

        $this->decide($this->who('registrar'), $approval)->assertRedirect();
        $this->decide($this->who('principal'), $approval)->assertSessionHasErrors('note');
    }

    public function test_certificates_can_be_verified_and_revoked(): void
    {
        $credential = $this->record('credential');
        $code = json_decode($credential->data, true)['code'];

        $this->get("/verify/certificate/{$code}")->assertOk()->assertSee('Valid');
        $this->web($this->who('registrar'))->post("/office/m/credential/{$credential->id}", ['status' => 'revoked'])->assertRedirect();
        $this->get("/verify/certificate/{$code}")->assertOk()->assertSee('Revoked');
        $this->get('/verify/certificate/HF-NOPE')->assertNotFound();
    }

    public function test_quiz_hides_answers_marks_on_the_server_and_allows_one_attempt(): void
    {
        $student = $this->who('nusrat');
        $quiz = DB::table('quizzes')->where('kind', 'preparatory')->first();
        $base = "/api/v1/campus/students/{$student->id}/quizzes/{$quiz->id}";

        $this->api($this->who('farhana'))->postJson("{$base}/start")->assertNotFound();
        $this->api($this->who('guardian'))->postJson("{$base}/start")->assertForbidden();
        $started = $this->api($student)->postJson("{$base}/start")->assertOk()->json('data');
        $this->assertArrayNotHasKey('answer', $started['questions'][0]);
        [$a, $b, $c, $d] = array_column($started['questions'], 'id');

        $this->api($student)->postJson("{$base}/submit", ['answers' => [$a => 1, $b => false, $c => 5, $d => [2, 0]]])
            ->assertOk()->assertJsonPath('data.score', 4);
        $this->api($student)->postJson("{$base}/submit", ['answers' => []])->assertStatus(422);
        $this->api($student)->postJson("{$base}/start")->assertStatus(422);
        $this->api($student)->getJson("{$base}/review")->assertStatus(422);
    }

    public function test_late_quiz_answers_do_not_count(): void
    {
        $student = $this->who('sadia');
        $quiz = DB::table('quizzes')->where('kind', 'preparatory')->first();
        $service = app(QuizService::class);
        $questions = $service->start($quiz, $student->id)['questions'];
        $this->travel(15)->minutes();

        $result = $service->submit($quiz, $student->id, [$questions[0]['id'] => 1]);

        $this->assertTrue($result['late']);
        $this->assertSame(0.0, $result['score']);
        $this->travelBack();
    }

    public function test_teacher_creates_a_quiz_only_with_valid_answers_for_an_assigned_class(): void
    {
        $teacher = $this->who('farhana');
        $class = $this->api($teacher)->getJson('/api/v1/campus/teaching/classes')->json('data.classes.0');
        $body = ['class_id' => $class['class_id'], 'section_id' => $class['section_id'], 'subject_id' => $class['subject_id'], 'title' => 'T', 'kind' => 'spot',
            'opens_at' => now()->addHour()->toIso8601String(), 'closes_at' => now()->addHours(2)->toIso8601String(), 'duration_minutes' => 5,
            'questions' => [['type' => 'choice', 'prompt' => 'P', 'marks' => 1, 'options' => ['a', 'b'], 'answer' => 5]]];

        $this->api($teacher)->postJson('/api/v1/campus/teaching/quizzes', $body)->assertStatus(422);
        $body['questions'][0]['answer'] = 1;
        $id = $this->api($teacher)->postJson('/api/v1/campus/teaching/quizzes', $body)->assertCreated()->json('data.id');

        // A spot test stays hidden until it opens.
        $ids = collect($this->api($this->who('nusrat'))->getJson('/api/v1/campus/students/' . $this->who('nusrat')->id . '/quizzes')->json('data.quizzes'))->pluck('id');
        $this->assertNotContains($id, $ids);
    }

    public function test_ballot_is_secret_single_and_personal(): void
    {
        $student = $this->who('nusrat');
        $election = DB::table('elections')->first();
        $candidate = DB::table('election_candidates')->where('election_id', $election->id)->value('id');
        $url = "/api/v1/campus/students/{$student->id}/elections/{$election->id}/vote";

        $this->api($this->who('guardian'))->postJson($url, ['candidate_id' => $candidate])->assertForbidden();
        $this->api($student)->postJson($url, ['candidate_id' => $candidate])->assertCreated();
        $this->api($student)->postJson($url, ['candidate_id' => $candidate])->assertStatus(422);

        $this->assertSame(1, DB::table('election_ballots')->where('election_id', $election->id)->count());
        $this->assertSame(['id', 'election_id', 'candidate_id'], Schema::getColumnListing('election_ballots'));
        $this->assertSame(['election_id', 'student_id'], Schema::getColumnListing('election_voters'));
        $this->assertFalse(DB::table('audit_logs')->where('action', 'like', 'election.vote%')->exists());
        // No counts are shown while voting is open.
        $this->api($student)->getJson("/api/v1/campus/students/{$student->id}/elections")->assertJsonPath('data.elections.0.candidates.0.votes', null);
        $this->web($this->who('office'))->post(route('office.elections.publish', $election->id))->assertStatus(422);
    }

    public function test_library_limits_and_late_charge_on_the_one_ledger(): void
    {
        $student = $this->who('nusrat');
        $overdue = DB::table('library_loans')->where('student_id', $student->id)->where('due_on', '<', now()->toDateString())->first();
        $free = DB::table('library_books')->whereNotIn('id', DB::table('library_loans')->pluck('book_id'))->value('id');
        $before = $this->due($student);

        $this->web($this->who('librarian'))->post(route('office.library.issue'), ['book_id' => $free, 'student_code' => $student->student_id, 'days' => 7])->assertSessionHasErrors('book');
        $this->web($this->who('librarian'))->post(route('office.library.close', $overdue->id), ['outcome' => 'returned'])->assertRedirect();

        $this->assertSame($before + 30, $this->due($student));
        $this->api($student)->postJson("/api/v1/campus/students/{$student->id}/library/{$free}/reserve")->assertCreated();
        $this->web($this->who('librarian'))->get('/office/fees')->assertForbidden();
    }

    public function test_messages_are_between_two_people_and_started_only_with_allowed_staff(): void
    {
        $student = $this->who('nusrat');
        $thread = DB::table('conversations')->first();

        $this->api($this->who('tahmid'))->getJson("/api/v1/campus/conversations/{$thread->id}")->assertNotFound();
        $this->api($this->who('office'))->getJson("/api/v1/campus/conversations/{$thread->id}")->assertNotFound();
        $this->api($student)->postJson('/api/v1/campus/conversations', ['student_id' => $student->id, 'staff_id' => $this->who('tahmid')->id, 'topic' => 'x', 'body' => 'x'])->assertStatus(422);
        $this->api($student)->postJson('/api/v1/campus/conversations', ['student_id' => $student->id, 'staff_id' => $this->who('farhana')->id, 'topic' => 'x', 'body' => 'x'])->assertCreated();
    }

    public function test_progress_calendar_and_portfolio_come_from_records(): void
    {
        $student = $this->who('nusrat');
        $base = "/api/v1/campus/students/{$student->id}";

        $this->api($student)->getJson("{$base}/progress")->assertOk()->assertJsonPath('data.parts.0.key', 'exams');
        $this->api($student)->getJson("{$base}/calendar")->assertOk()->assertJsonStructure(['data' => ['events' => [['kind', 'title', 'date']]]]);
        $this->api($student)->getJson("{$base}/portfolio")->assertOk()->assertSee('Debate competition winner');
        $this->api($this->who('sadia'))->getJson("{$base}/portfolio")->assertNotFound();
    }

    public function test_marksheet_and_admit_card_open_only_from_a_signed_link(): void
    {
        $student = $this->who('nusrat');
        $data = $this->api($student)->getJson("/api/v1/campus/students/{$student->id}/results")->assertOk()->json('data');
        $marksheet = $data['results'][0]['marksheet_url'];

        $this->get($marksheet)->assertOk()->assertSee('Marksheet')->assertSee($student->name);
        $this->get($data['admit_cards'][0]['url'])->assertOk()->assertSee('Admit card');
        // Changing the student in the link breaks the signature.
        $this->get(str_replace("/marksheet/{$student->id}/", '/marksheet/' . $this->who('sadia')->id . '/', $marksheet))->assertForbidden();

        DB::table('result_publications')->where('id', $data['results'][0]['id'])->update(['status' => 'returned']);
        $this->get($marksheet)->assertNotFound();
    }

    public function test_the_app_module_list_matches_the_role(): void
    {
        $keys = fn (User $u) => collect($this->api($u)->getJson('/api/v1/campus/modules')->assertOk()->json('data.modules'))->pluck('key');

        $family = $keys($this->who('nusrat'));
        $this->assertContains('activity', $family);
        $this->assertNotContains('leave_request', $family);
        $this->assertNotContains('vendor', $family);
        $teacher = $keys($this->who('farhana'));
        $this->assertContains('leave_request', $teacher);
        $this->assertNotContains('fee_waiver', $teacher);
    }

    public function test_the_workflow_inbox_only_lists_what_you_may_decide(): void
    {
        $workflow = app(Workflow::class);

        $this->assertTrue($workflow->inbox($this->who('accounts'))->contains('subject_type', 'scholarship_application'));
        $this->assertFalse($workflow->inbox($this->who('accounts'))->contains('subject_type', 'leave_request'));
        $this->assertFalse($workflow->inbox($this->who('farhana'))->count() > 0);
    }
}
