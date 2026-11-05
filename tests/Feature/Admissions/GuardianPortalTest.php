<?php

namespace Tests\Feature\Admissions;

use App\Mail\GuardianOtpMail;
use App\Models\User;
use App\Modules\Admissions\Models\Application;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Admissions\Support\BuildsAdmissions;
use Tests\TestCase;

class GuardianPortalTest extends TestCase
{
    use DatabaseTransactions, BuildsAdmissions;

    private const EMAIL = 'guardian.test@example.com';
    private const PHONE = '01755512345';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        Mail::fake();
    }

    /** Signs a guardian in through the real steps: details, email code (test code 123456), verify. */
    private function signInGuardian(string $email = self::EMAIL, string $name = 'Karim Rahman', string $phone = self::PHONE): void
    {
        $program = $this->program('PG-2026');

        $this->post(route('admissions.guardian.send', ['program' => $program->id]), [
            'guardian_name' => $name, 'relation' => 'FATHER', 'email' => $email, 'phone' => $phone,
        ])->assertRedirect(route('admissions.guardian.start', ['program' => $program->id]));

        $this->post(route('admissions.guardian.verify.post'), ['code' => '123456'])
            ->assertRedirect(route('guardian.dashboard'));
    }

    public function test_apply_now_opens_the_guardian_step(): void
    {
        $program = $this->program('PG-2026');

        $this->get(route('admissions.guardian.start', ['program' => $program->id]))
            ->assertOk()
            ->assertSee('Guardian')
            ->assertSee('Send OTP')
            ->assertDontSee('Enter the 6-digit OTP');
    }

    public function test_otp_step_appears_on_the_same_page_after_sending(): void
    {
        $program = $this->program('PG-2026');

        $this->post(route('admissions.guardian.send', ['program' => $program->id]), [
            'guardian_name' => 'Karim Rahman', 'relation' => 'FATHER', 'email' => self::EMAIL, 'phone' => self::PHONE,
        ])->assertRedirect(route('admissions.guardian.start', ['program' => $program->id]));

        $this->get(route('admissions.guardian.start', ['program' => $program->id]))
            ->assertOk()
            ->assertSee('Enter the 6-digit OTP')
            ->assertSee('Verify OTP')
            ->assertSee(self::EMAIL)
            ->assertDontSee('name="guardian_name"');
    }

    public function test_the_code_is_emailed_to_the_guardian(): void
    {
        $program = $this->program('PG-2026');

        $this->post(route('admissions.guardian.send', ['program' => $program->id]), [
            'guardian_name' => 'Karim Rahman', 'relation' => 'FATHER', 'email' => self::EMAIL, 'phone' => self::PHONE,
        ])->assertRedirect();

        Mail::assertSent(GuardianOtpMail::class, fn ($mail) => $mail->hasTo(self::EMAIL));
    }

    public function test_a_wrong_code_is_refused(): void
    {
        $program = $this->program('PG-2026');
        $this->post(route('admissions.guardian.send', ['program' => $program->id]), [
            'guardian_name' => 'Karim Rahman', 'relation' => 'FATHER', 'email' => self::EMAIL, 'phone' => self::PHONE,
        ]);

        $this->post(route('admissions.guardian.verify.post'), ['code' => '000000'])
            ->assertSessionHasErrors('code');
        $this->get(route('guardian.dashboard'))->assertRedirect();
    }

    public function test_test_code_123456_signs_the_guardian_in_and_stores_them(): void
    {
        $this->signInGuardian();

        $guardian = User::where('email', self::EMAIL)->firstOrFail();
        $this->assertEquals(self::PHONE, $guardian->phone_number);
        $this->get(route('guardian.dashboard'))->assertOk()->assertSee('Karim Rahman')->assertSee('Guardian portal');
    }

    public function test_guardian_applies_and_the_application_belongs_to_the_guardian(): void
    {
        $this->signInGuardian();
        $program = $this->program('PG-2026');

        $this->get(route('guardian.apply', ['program' => $program->id]))
            ->assertOk()
            ->assertSee('Guardian portal');

        $this->post(route('admissions.store', ['program' => $program->id]), [
            'candidate_name' => 'Guardian Child',
            'phone' => self::PHONE,
            'candidate_dob' => $this->dobForProgram($program, 4),
            'gender' => 'MALE',
            'current_address' => 'Savar, Dhaka',
            'father_name' => 'Karim Rahman',
            'father_phone' => self::PHONE,
            'declaration' => '1',
            'photo' => UploadedFile::fake()->image('p.png'),
            'birth_cert' => UploadedFile::fake()->image('b.png'),
        ])->assertRedirect(route('guardian.dashboard'));

        $application = Application::where('applicant_name', 'Guardian Child')->firstOrFail();
        $this->assertEquals(User::where('email', self::EMAIL)->value('id'), $application->applicant_user_id);
        $this->assertEquals('PENDING_APP_FEE', $application->status);
    }

    public function test_guardian_cannot_apply_with_a_different_phone_number(): void
    {
        $this->signInGuardian();
        $program = $this->program('PG-2026');

        $this->post(route('admissions.store', ['program' => $program->id]), [
            'candidate_name' => 'Wrong Phone', 'phone' => '01799999999', 'candidate_dob' => $this->dobForProgram($program, 4),
            'gender' => 'MALE', 'current_address' => 'X', 'father_name' => 'F', 'father_phone' => '01799999999', 'declaration' => '1',
        ])->assertSessionHasErrors('phone');

        $this->assertDatabaseMissing('applications', ['applicant_name' => 'Wrong Phone']);
    }

    public function test_guardian_sees_their_own_application_but_not_someone_elses(): void
    {
        $this->signInGuardian();
        $mine = $this->makeApplication(['applicant_name' => 'Mine', 'status' => 'SUBMITTED']);
        $mine->update(['applicant_user_id' => User::where('email', self::EMAIL)->value('id')]);
        $theirs = $this->makeApplication(['applicant_name' => 'Theirs', 'status' => 'SUBMITTED']);

        $this->get("/admissions/applications/{$mine->id}")->assertOk()->assertSee('Guardian portal');
        $this->get("/admissions/applications/{$theirs->id}")->assertStatus(404);
    }

    public function test_sign_out_clears_the_guardian_session(): void
    {
        $this->signInGuardian();

        $this->post(route('guardian.logout'))->assertRedirect();

        $this->get(route('guardian.dashboard'))->assertRedirect();
    }
}
