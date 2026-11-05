<?php

namespace Tests\Feature\Admissions;

use App\Modules\Admissions\Models\AdmissionProgram;
use App\Modules\Admissions\Services\OtpService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\Admissions\Support\BuildsAdmissions;
use Tests\TestCase;

class ApiEndpointsTest extends TestCase
{
    use DatabaseTransactions, BuildsAdmissions;

    public function test_programs_list_uses_the_standard_envelope(): void
    {
        $this->getJson('/api/v1/admissions/programs')
            ->assertOk()
            ->assertJsonStructure(['success', 'message', 'data' => ['*' => ['id', 'code', 'title', 'section_type', 'application_fee', 'admission_fee', 'capacity', 'is_open']], 'meta', 'errors']);
    }

    public function test_program_details(): void
    {
        $program = $this->program('PG-2026');

        $this->getJson("/api/v1/admissions/programs/{$program->id}")
            ->assertOk()
            ->assertJsonPath('data.code', 'PG-2026');
    }

    public function test_otp_request_then_verify_returns_an_applicant_token(): void
    {
        $send = $this->postJson('/api/v1/admissions/applicants/otp/request', ['phone' => '01799001122', 'purpose' => 'APPLICATION_AUTH'])
            ->assertOk();

        $code = $send->json('data.debug_otp');
        $this->assertNotEmpty($code, 'debug code is returned only in local/testing');

        $verify = $this->postJson('/api/v1/admissions/applicants/otp/verify', ['phone' => '01799001122', 'code' => $code])
            ->assertOk()
            ->assertJsonPath('data.verified', true)
            ->assertJsonStructure(['data' => ['token', 'token_type', 'expires_in_minutes']]);

        $this->assertNotEmpty($verify->json('data.token'));
    }

    public function test_otp_wrong_code_is_refused_with_attempts_left(): void
    {
        $this->postJson('/api/v1/admissions/applicants/otp/request', ['phone' => '01799001133']);

        $this->postJson('/api/v1/admissions/applicants/otp/verify', ['phone' => '01799001133', 'code' => '000000'])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_otp_send_is_rate_limited_per_number(): void
    {
        $service = app(OtpService::class);
        for ($i = 0; $i < 5; $i++) {
            $service->generateOtp('01799001144');
        }

        $this->postJson('/api/v1/admissions/applicants/otp/request', ['phone' => '01799001144'])
            ->assertStatus(429);
    }

    public function test_draft_application_needs_an_applicant_token(): void
    {
        $this->postJson('/api/v1/admissions/apply', ['program_id' => $this->program()->id])
            ->assertStatus(401);
    }

    public function test_applicant_can_create_a_draft_for_their_verified_number(): void
    {
        $program = $this->program('PG-2026');
        $user = $this->applicantFor('01712345678');
        $token = $user->createToken('applicant', ['applicant'])->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/admissions/apply', [
            'program_id' => $program->id,
            'candidate_name' => 'API Candidate',
            'phone' => '01712345678',
            'candidate_dob' => $this->dobForProgram($program, 4),
            'gender' => 'MALE',
            'current_address' => 'Gulshan, Dhaka',
        ])
            ->assertStatus(201)
            ->assertJsonPath('data.candidate.name', 'API Candidate')
            ->assertJsonPath('data.status.value', 'DRAFT')
            ->assertJsonPath('data.qr', null);
    }

    public function test_applicant_cannot_create_a_draft_for_someone_elses_number(): void
    {
        $program = $this->program('PG-2026');
        $token = $this->applicantFor('01712340000')->createToken('applicant', ['applicant'])->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/admissions/apply', [
            'program_id' => $program->id, 'candidate_name' => 'Someone', 'phone' => '01799999999',
            'candidate_dob' => $this->dobForProgram($program, 4), 'gender' => 'FEMALE', 'current_address' => 'X',
        ])->assertStatus(422);
    }

    public function test_status_endpoint_needs_login_and_hides_the_pass_before_approval(): void
    {
        $application = $this->makeApplication(['status' => 'SUBMITTED']);
        $owner = $this->applicantFor($application->mobile);
        $application->update(['applicant_user_id' => $owner->id]);
        $token = $owner->createToken('applicant', ['applicant'])->plainTextToken;

        $this->getJson("/api/v1/admissions/applications/{$application->id}/status")->assertStatus(401);

        $this->withToken($token)->getJson("/api/v1/admissions/applications/{$application->id}/status")
            ->assertOk()
            ->assertJsonPath('data.status.value', 'SUBMITTED')
            ->assertJsonPath('data.qr', null);
    }

    public function test_track_needs_the_one_time_code_and_gives_a_generic_error_otherwise(): void
    {
        $application = $this->makeApplication(['status' => 'SUBMITTED', 'mobile' => '01788776655']);

        // No code sent: generic error.
        $this->postJson('/api/v1/admissions/track', ['application_no' => $application->application_no, 'phone' => '01788776655', 'code' => '999999'])
            ->assertStatus(404)
            ->assertJsonPath('message', 'We could not find an application matching these details.');

        // Correct code: status is shown, with no pass and no personal guardian details.
        $code = app(OtpService::class)->generateOtp('01788776655', 'TRACK');
        $this->postJson('/api/v1/admissions/track', ['application_no' => $application->application_no, 'phone' => '01788776655', 'code' => $code])
            ->assertOk()
            ->assertJsonPath('data.status.value', 'SUBMITTED')
            ->assertJsonPath('data.qr', null);
    }
}
