<?php

namespace Tests\Feature\Admissions;

use App\Models\User;
use App\Modules\Admissions\Enums\PaymentType;
use App\Modules\Admissions\Services\OtpService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Admissions\Support\BuildsAdmissions;
use Tests\TestCase;

class WebPortalTest extends TestCase
{
    use DatabaseTransactions, BuildsAdmissions;

    public function test_public_pages_load(): void
    {
        $this->get('/admissions')->assertOk()->assertSee('Playgroup Admission 2026');
        $this->get('/admissions/track')->assertOk()->assertSee('Send me a code');
    }

    public function test_the_apply_form_is_hidden_until_the_mobile_is_verified(): void
    {
        $program = $this->program('PG-2026');

        $this->get("/admissions/apply/{$program->id}")
            ->assertOk()
            ->assertSee('Verify your mobile number first')
            ->assertDontSee('name="candidate_name"', false);
    }

    public function test_applicant_verifies_by_code_then_submits_the_form(): void
    {
        Storage::fake('local');
        $program = $this->program('PG-2026');
        $phone = '01799887766';

        $code = app(OtpService::class)->generateOtp($phone);
        $this->post(route('admissions.otp.verify'), ['phone' => $phone, 'code' => $code])
            ->assertSessionHas('status');

        $this->get("/admissions/apply/{$program->id}")->assertSee('verified', false);

        $response = $this->post("/admissions/apply/{$program->id}", [
            'candidate_name' => 'Web Child',
            'phone' => $phone,
            'candidate_dob' => $this->dobForProgram($program, 4),
            'gender' => 'MALE',
            'current_address' => 'Web Road, Savar',
            'father_name' => 'Web Father',
            'father_phone' => '01788990011',
            'declaration' => '1',
            'photo' => UploadedFile::fake()->image('p.png'),
            'birth_cert' => UploadedFile::fake()->image('b.png'),
        ]);

        $application = \App\Modules\Admissions\Models\Application::where('applicant_name', 'Web Child')->firstOrFail();
        $response->assertRedirect(route('admissions.show', ['id' => $application->id]));
        $this->assertEquals('PENDING_APP_FEE', $application->status);
        $this->assertEquals(2, $application->documents()->count());
    }

    public function test_web_submit_without_verification_is_refused(): void
    {
        $program = $this->program('PG-2026');

        $this->post("/admissions/apply/{$program->id}", [
            'candidate_name' => 'No Verify', 'phone' => '01700111222', 'candidate_dob' => $this->dobForProgram($program, 4),
            'gender' => 'MALE', 'current_address' => 'X', 'father_name' => 'F', 'father_phone' => '01700111223', 'declaration' => '1',
        ])->assertSessionHasErrors('phone');

        $this->assertDatabaseMissing('applications', ['applicant_name' => 'No Verify']);
    }

    public function test_application_page_is_only_for_the_session_that_verified_it_or_for_staff(): void
    {
        $application = $this->makeApplication(['status' => 'SUBMITTED']);

        $this->get("/admissions/applications/{$application->id}")->assertStatus(404);
        $this->get("/admissions/applications/{$application->application_no}")->assertStatus(404);
    }

    public function test_track_page_sends_a_code_and_shows_the_application_only_after_verifying(): void
    {
        $application = $this->makeApplication(['status' => 'SUBMITTED', 'mobile' => '01700555555']);

        $this->post(route('admissions.track.send'), ['application_number' => $application->application_no, 'phone' => '01700555555'])
            ->assertSessionHas('status');

        // Wrong code.
        $this->post(route('admissions.track.verify'), ['code' => '000000'])->assertSessionHasErrors('code');
        $this->get(route('admissions.track'))->assertDontSee($application->application_no);

        // Right code, from the same session.
        // The code sent above is what the applicant would type; generate a fresh one (invalidating the earlier) for the test.
        $code = app(OtpService::class)->generateOtp('01700555555', 'TRACK');
        $this->post(route('admissions.track.verify'), ['code' => $code]);

        $this->get(route('admissions.track'))->assertSee($application->application_no)->assertSee('Submitted');
    }

    public function test_mock_checkout_page_is_only_for_the_applicants_own_session(): void
    {
        $application = $this->makeApplication(['status' => 'PENDING_APP_FEE']);
        $payment = app(\App\Modules\Admissions\Actions\InitiatePaymentAction::class)->execute($application, PaymentType::APPLICATION_FEE, 'mock')['payment'];

        $this->get(route('admissions.payments.mock.checkout', ['payment' => $payment->id]))->assertStatus(404);

        $this->withSession(['admissions.track_application_id' => $application->id])
            ->get(route('admissions.payments.mock.checkout', ['payment' => $payment->id]))
            ->assertOk()
            ->assertSee('MCC Payment Gateway');
    }

    public function test_mock_checkout_success_pays_through_the_same_callback_path(): void
    {
        $application = $this->makeApplication(['status' => 'PENDING_APP_FEE']);
        $payment = app(\App\Modules\Admissions\Actions\InitiatePaymentAction::class)->execute($application, PaymentType::APPLICATION_FEE, 'mock')['payment'];

        $this->withSession(['admissions.track_application_id' => $application->id])
            ->post(route('admissions.payments.mock.process', ['payment' => $payment->id]), ['action' => 'success'])
            ->assertRedirect();

        $this->assertEquals('SUBMITTED', $application->fresh()->status);
    }

    public function test_ipn_without_a_known_sslcommerz_transaction_is_refused(): void
    {
        $this->post(route('admissions.payments.ipn'), ['tran_id' => 'NOPE'])->assertStatus(404);
    }

    public function test_admin_pages_redirect_guests_to_login(): void
    {
        $this->get('/admin/admissions')->assertRedirect();
        $this->get('/admin/admissions/applications/' . $this->makeApplication()->id)->assertRedirect();
    }

    public function test_officer_sees_the_dashboard_and_a_student_does_not(): void
    {
        $this->actingAs($this->staff('officer@mcc.edu.bd'))->get('/admin/admissions')->assertOk()->assertSee('Total Applications');

        $student = User::where('email', 'student@gmail.com')->firstOrFail();
        $this->actingAs($student)->get('/admin/admissions')->assertStatus(403);
    }

    public function test_evaluation_page_renders_after_a_decision(): void
    {
        $application = $this->makeApplication(['status' => 'UNDER_REVIEW']);
        app(\App\Modules\Admissions\Actions\RecordCouncilDecisionAction::class)->execute(
            $application, \App\Modules\Admissions\Enums\CouncilDecision::APPROVED, 'Good', $this->staff('council@mcc.edu.bd')
        );

        $this->actingAs($this->staff('council@mcc.edu.bd'))
            ->get("/admin/admissions/applications/{$application->id}")
            ->assertOk()
            ->assertSee('Approved');
    }

    public function test_web_decision_refuses_rejection_without_a_reason(): void
    {
        $application = $this->makeApplication(['status' => 'ASSESSED']);

        $this->actingAs($this->staff('council@mcc.edu.bd'))
            ->post("/admin/admissions/applications/{$application->id}/decision", ['decision' => 'REJECTED'])
            ->assertSessionHasErrors('remarks');

        $this->assertEquals('ASSESSED', $application->fresh()->status);
    }
}
