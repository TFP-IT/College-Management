<?php

namespace Tests\Feature\Admissions;

use App\Mail\GuardianOtpMail;
use App\Models\User;
use App\Modules\Admissions\Models\Application;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Admissions\Support\BuildsAdmissions;
use Tests\TestCase;

class GuardianApiTest extends TestCase
{
    use DatabaseTransactions, BuildsAdmissions;

    private const EMAIL = 'api.guardian@example.com';
    private const PHONE = '01755598765';

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function requestOtp(string $programId, array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/v1/admissions/guardian/otp/request', array_merge([
            'program_id' => $programId,
            'guardian_name' => 'Karim Rahman',
            'relation' => 'FATHER',
            'email' => self::EMAIL,
            'phone' => self::PHONE,
        ], $overrides));
    }

    /** Test code 123456 is accepted in the testing environment. Returns the guardian token. */
    private function guardianToken(string $programId): string
    {
        $this->requestOtp($programId)->assertOk();

        return $this->postJson('/api/v1/admissions/guardian/otp/verify', ['email' => self::EMAIL, 'code' => '123456'])
            ->assertOk()
            ->json('data.token');
    }

    public function test_the_code_is_emailed_and_the_response_names_the_email(): void
    {
        $program = $this->program('PG-2026');

        $this->requestOtp($program->id)
            ->assertOk()
            ->assertJsonPath('data.sent', true)
            ->assertJsonPath('data.email', self::EMAIL);

        Mail::assertSent(GuardianOtpMail::class, fn ($mail) => $mail->hasTo(self::EMAIL));
    }

    public function test_verify_returns_a_guardian_token_with_applicant_and_guardian_abilities(): void
    {
        $program = $this->program('PG-2026');
        $this->requestOtp($program->id);

        $this->postJson('/api/v1/admissions/guardian/otp/verify', ['email' => self::EMAIL, 'code' => '123456'])
            ->assertOk()
            ->assertJsonPath('data.program_id', $program->id)
            ->assertJsonPath('data.guardian.relation', 'FATHER')
            ->assertJsonPath('data.guardian.phone', self::PHONE)
            ->assertJsonStructure(['data' => ['token', 'token_type', 'expires_in_minutes', 'guardian' => ['name', 'email', 'phone', 'relation']]]);
    }

    public function test_a_wrong_code_is_refused(): void
    {
        $program = $this->program('PG-2026');
        $this->requestOtp($program->id);

        $this->postJson('/api/v1/admissions/guardian/otp/verify', ['email' => self::EMAIL, 'code' => '000000'])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_verify_without_a_request_is_refused(): void
    {
        $this->postJson('/api/v1/admissions/guardian/otp/verify', ['email' => 'nobody@example.com', 'code' => '123456'])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_a_closed_programme_cannot_start_a_guardian_otp(): void
    {
        $program = $this->program('PG-2026');
        $program->update(['ends_at' => now()->subDay()]);

        $this->requestOtp($program->id)->assertStatus(422);
        Mail::assertNotSent(GuardianOtpMail::class);
    }

    public function test_guardian_token_applies_and_is_recorded_as_the_primary_guardian(): void
    {
        $program = $this->program('PG-2026');
        $token = $this->guardianToken($program->id);

        $response = $this->withToken($token)->postJson('/api/v1/admissions/apply', [
            'program_id' => $program->id,
            'candidate_name' => 'API Child',
            'phone' => self::PHONE,
            'candidate_dob' => $this->dobForProgram($program, 4),
            'gender' => 'MALE',
            'current_address' => 'Savar, Dhaka',
            'guardian_relation' => 'FATHER',
        ])->assertStatus(201);

        $application = Application::with('guardians')->findOrFail($response->json('data.id'));
        $guardian = User::where('email', self::EMAIL)->firstOrFail();

        $this->assertEquals($guardian->id, $application->applicant_user_id);
        $this->assertCount(1, $application->guardians);
        $this->assertTrue((bool) $application->guardians->first()->is_primary);
        $this->assertEquals('Karim Rahman', $application->guardians->first()->full_name);
    }

    public function test_guardian_cannot_apply_with_a_different_phone(): void
    {
        $program = $this->program('PG-2026');
        $token = $this->guardianToken($program->id);

        $this->withToken($token)->postJson('/api/v1/admissions/apply', [
            'program_id' => $program->id,
            'candidate_name' => 'Wrong Phone',
            'phone' => '01799999999',
            'candidate_dob' => $this->dobForProgram($program, 4),
            'gender' => 'MALE',
            'current_address' => 'Savar, Dhaka',
        ])->assertStatus(422);

        $this->assertDatabaseMissing('applications', ['applicant_name' => 'Wrong Phone']);
    }
}
