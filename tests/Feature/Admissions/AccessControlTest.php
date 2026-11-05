<?php

namespace Tests\Feature\Admissions;

use App\Models\User;
use App\Modules\Admissions\Services\OtpService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\Admissions\Support\BuildsAdmissions;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use DatabaseTransactions, BuildsAdmissions;

    private function applicantToken(string $mobile): string
    {
        return $this->applicantFor($mobile)->createToken('applicant', ['applicant'])->plainTextToken;
    }

    public function test_staff_routes_refuse_requests_without_a_token(): void
    {
        $this->getJson('/api/v1/admissions/council/applications')->assertStatus(401);
        $this->postJson('/api/v1/admissions/council/applications/x/decision', ['decision' => 'APPROVED'])->assertStatus(401);
    }

    public function test_an_applicant_token_cannot_reach_staff_routes(): void
    {
        $token = $this->applicantToken('01700100100');

        $this->withToken($token)->getJson('/api/v1/admissions/council/applications')->assertStatus(403);
    }

    public function test_a_legacy_student_token_cannot_approve_an_application(): void
    {
        $application = $this->makeApplication(['status' => 'ASSESSED']);
        // A student signs in through the existing app login, which issues an all-abilities token.
        $student = $this->postJson('/api/app_login', ['role' => 'student', 'login_id' => 'STD-1001', 'password' => '12345678'])->json('token');

        $this->withToken($student)->postJson("/api/v1/admissions/council/applications/{$application->id}/decision", ['decision' => 'APPROVED'])
            ->assertStatus(403);
        $this->assertEquals('ASSESSED', $application->fresh()->status);
    }

    public function test_a_legacy_student_token_cannot_list_every_application(): void
    {
        $student = $this->postJson('/api/app_login', ['role' => 'student', 'login_id' => 'STD-1001', 'password' => '12345678'])->json('token');

        $this->withToken($student)->getJson('/api/v1/admissions/council/applications')->assertStatus(403);
    }

    public function test_an_applicant_cannot_read_another_applicants_application(): void
    {
        $mine = $this->applicantFor('01700200200');
        $theirs = $this->makeApplication(['status' => 'SUBMITTED', 'mobile' => '01700200299']);
        $this->applicantFor('01700200299');

        $token = $mine->createToken('applicant', ['applicant'])->plainTextToken;
        $this->withToken($token)->getJson("/api/v1/admissions/applications/{$theirs->id}")->assertStatus(404);
    }

    public function test_an_applicant_cannot_edit_another_applicants_draft(): void
    {
        $theirs = $this->makeApplication(['status' => 'DRAFT', 'applicant_name' => 'Original']);
        $token = $this->applicantToken('01700300300');

        $this->withToken($token)->putJson("/api/v1/admissions/applications/{$theirs->id}", ['candidate_name' => 'HIJACKED'])
            ->assertStatus(404);
        $this->assertEquals('Original', $theirs->fresh()->applicant_name);
    }

    public function test_an_applicant_cannot_pay_for_another_applicants_application(): void
    {
        $theirs = $this->makeApplication(['status' => 'PENDING_APP_FEE']);
        $token = $this->applicantToken('01700400400');

        $this->withToken($token)->postJson("/api/v1/admissions/applications/{$theirs->id}/pay", ['payment_type' => 'APPLICATION_FEE', 'provider' => 'mock'])
            ->assertStatus(404);
        $this->assertDatabaseCount('admission_payments', 0);
    }

    public function test_the_pass_is_shown_to_the_owner_after_approval_only(): void
    {
        $application = $this->makeApplication(['status' => 'UNDER_REVIEW']);
        $owner = $this->applicantFor($application->mobile);
        $application->update(['applicant_user_id' => $owner->id]);
        $token = $owner->createToken('applicant', ['applicant'])->plainTextToken;

        $this->withToken($token)->getJson("/api/v1/admissions/applications/{$application->id}/status")
            ->assertJsonPath('data.qr', null);

        $application->update(['status' => 'APPROVED']);
        $this->issueQr($application);

        $this->withToken($token)->getJson("/api/v1/admissions/applications/{$application->id}/status")
            ->assertJsonPath('data.qr.token', $application->fresh()->qr_token);
    }

    public function test_staff_login_refuses_a_user_without_admissions_access(): void
    {
        $this->postJson('/api/v1/admissions/auth/login', ['email' => 'teacher@gmail.com', 'password' => '12345678'])
            ->assertStatus(403); // correct password, but no admissions permission

        $this->postJson('/api/v1/admissions/auth/login', ['email' => 'student@gmail.com', 'password' => '12345678'])
            ->assertStatus(403);
    }

    public function test_staff_login_with_wrong_password_is_refused(): void
    {
        $this->postJson('/api/v1/admissions/auth/login', ['email' => 'council@mcc.edu.bd', 'password' => 'wrong'])
            ->assertStatus(401);
    }

    public function test_council_staff_can_read_the_queue_and_the_officer_cannot_decide(): void
    {
        $council = $this->postJson('/api/v1/admissions/auth/login', ['email' => 'council@mcc.edu.bd', 'password' => 'Council@123'])->json('data.token');
        $officer = $this->postJson('/api/v1/admissions/auth/login', ['email' => 'officer@mcc.edu.bd', 'password' => 'Officer@123'])->json('data.token');
        $application = $this->makeApplication(['status' => 'ASSESSED']);

        $this->withToken($council)->getJson('/api/v1/admissions/council/applications')->assertOk();
        $this->withToken($officer)->postJson("/api/v1/admissions/council/applications/{$application->id}/decision", ['decision' => 'APPROVED'])
            ->assertStatus(403);
    }

    public function test_track_otp_is_locked_after_five_wrong_codes(): void
    {
        $application = $this->makeApplication(['status' => 'SUBMITTED', 'mobile' => '01700500500']);
        app(OtpService::class)->generateOtp('01700500500', 'TRACK');

        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/api/v1/admissions/track', ['application_no' => $application->application_no, 'phone' => '01700500500', 'code' => '000000']);
        }

        $this->postJson('/api/v1/admissions/track', ['application_no' => $application->application_no, 'phone' => '01700500500', 'code' => '000000'])
            ->assertStatus(404);
    }
}
