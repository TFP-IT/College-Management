<?php

namespace Tests\Feature;

use App\Mail\TeacherSetupOtpMail;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TeacherOtpSetupTest extends TestCase
{
    use DatabaseTransactions;

    private function makeTeacher(array $attributes = []): User
    {
        return User::create(array_merge([
            'name' => 'Test Teacher',
            'email' => 'otp.teacher@example.test',
            'role_id' => 3,
            'school_id' => 1,
            'status' => 1,
            'password' => null,
        ], $attributes));
    }

    private function requestOtp(string $email): string
    {
        $this->postJson('/api/auth/teacher/request-setup-otp', ['email' => $email])
            ->assertOk()
            ->assertJsonPath('message', 'If this email is registered, an OTP has been sent.');

        $code = null;
        Mail::assertSent(TeacherSetupOtpMail::class, function ($mail) use (&$code) {
            $code = $mail->code;
            return true;
        });

        return $code;
    }

    public function test_full_first_time_setup_then_login_with_username(): void
    {
        Mail::fake();
        $teacher = $this->makeTeacher();

        $otp = $this->requestOtp('Otp.Teacher@example.test');
        $this->assertDatabaseMissing('otp_codes', ['code_hash' => $otp]);

        $verify = $this->postJson('/api/auth/teacher/verify-setup-otp', ['email' => $teacher->email, 'otp' => $otp])
            ->assertOk()
            ->assertJsonPath('teacher.needs_username', true);
        $setupToken = $verify->json('setup_token');

        $this->postJson('/api/auth/teacher/verify-setup-otp', ['email' => $teacher->email, 'otp' => $otp])
            ->assertStatus(400);

        $this->postJson('/api/auth/teacher/complete-setup', [
            'setup_token' => $setupToken,
            'username' => 'Rafiq_MCC',
            'password' => 'secret-pass-1',
            'password_confirmation' => 'secret-pass-1',
        ])->assertOk()->assertJsonStructure(['token', 'user']);

        $teacher->refresh();
        $this->assertSame('rafiq_mcc', $teacher->username);
        $this->assertTrue(Hash::check('secret-pass-1', $teacher->password));

        $this->postJson('/api/auth/teacher/complete-setup', [
            'setup_token' => $setupToken,
            'password' => 'another-pass-2',
            'password_confirmation' => 'another-pass-2',
        ])->assertStatus(401);

        $this->postJson('/api/app_login', ['role' => 'teacher', 'login_id' => 'rafiq_mcc', 'password' => 'secret-pass-1'])
            ->assertOk();
    }

    public function test_unknown_and_inactive_emails_get_the_same_response_and_no_mail(): void
    {
        Mail::fake();
        $this->makeTeacher(['email' => 'inactive@example.test', 'status' => 0]);

        foreach (['nobody@example.test', 'inactive@example.test'] as $email) {
            $this->postJson('/api/auth/teacher/request-setup-otp', ['email' => $email])
                ->assertOk()
                ->assertJsonPath('message', 'If this email is registered, an OTP has been sent.');
        }

        Mail::assertNothingSent();
    }

    public function test_open_signup_creates_a_teacher_only_after_the_code_is_verified(): void
    {
        Mail::fake();
        config(['teacher_auth.open_signup' => true]);

        $otp = $this->requestOtp('new.person@example.test');
        $this->assertDatabaseMissing('users', ['email' => 'new.person@example.test']);

        $this->postJson('/api/auth/teacher/verify-setup-otp', ['email' => 'new.person@example.test', 'otp' => $otp])
            ->assertOk()
            ->assertJsonPath('teacher.needs_username', true);

        $this->assertDatabaseHas('users', ['email' => 'new.person@example.test', 'role_id' => 3, 'status' => 1]);
    }

    public function test_open_signup_never_turns_an_existing_non_teacher_into_a_teacher(): void
    {
        Mail::fake();
        config(['teacher_auth.open_signup' => true]);
        $this->makeTeacher(['email' => 'student@example.test', 'role_id' => 7]);

        $this->postJson('/api/auth/teacher/request-setup-otp', ['email' => 'student@example.test'])->assertOk();

        Mail::assertNothingSent();
    }

    public function test_requests_are_limited_to_one_per_minute_per_email(): void
    {
        Mail::fake();
        $this->makeTeacher();

        $this->postJson('/api/auth/teacher/request-setup-otp', ['email' => 'otp.teacher@example.test'])->assertOk();
        $this->postJson('/api/auth/teacher/request-setup-otp', ['email' => 'otp.teacher@example.test'])->assertStatus(429);
    }

    public function test_code_is_destroyed_after_five_wrong_attempts(): void
    {
        Mail::fake();
        $teacher = $this->makeTeacher();
        $otp = $this->requestOtp($teacher->email);
        $wrong = $otp === '111111' ? '222222' : '111111';

        for ($i = 0; $i < 4; $i++) {
            $this->postJson('/api/auth/teacher/verify-setup-otp', ['email' => $teacher->email, 'otp' => $wrong])->assertStatus(400);
        }
        $this->postJson('/api/auth/teacher/verify-setup-otp', ['email' => $teacher->email, 'otp' => $wrong])->assertStatus(429);

        $this->postJson('/api/auth/teacher/verify-setup-otp', ['email' => $teacher->email, 'otp' => $otp])->assertStatus(400);
    }

    public function test_username_must_be_unique_and_well_formed(): void
    {
        Mail::fake();
        $this->makeTeacher(['email' => 'first@example.test', 'username' => 'taken_name']);
        $teacher = $this->makeTeacher();

        $otp = $this->requestOtp($teacher->email);
        $token = $this->postJson('/api/auth/teacher/verify-setup-otp', ['email' => $teacher->email, 'otp' => $otp])->json('setup_token');

        $payload = ['setup_token' => $token, 'password' => 'secret-pass-1', 'password_confirmation' => 'secret-pass-1'];

        $this->postJson('/api/auth/teacher/complete-setup', $payload)->assertStatus(422);
        $this->postJson('/api/auth/teacher/complete-setup', $payload + ['username' => 'bad name!'])->assertStatus(422);
        $this->postJson('/api/auth/teacher/complete-setup', $payload + ['username' => 'taken_name'])->assertStatus(422);
        $this->postJson('/api/auth/teacher/complete-setup', $payload + ['username' => 'fresh_name'])->assertOk();
    }

    public function test_forgot_password_keeps_username_and_revokes_old_sessions(): void
    {
        Mail::fake();
        $teacher = $this->makeTeacher(['username' => 'existing_user', 'password' => Hash::make('old-password')]);
        $teacher->createToken('auth-token');

        $otp = $this->requestOtp($teacher->email);
        $verify = $this->postJson('/api/auth/teacher/verify-setup-otp', ['email' => $teacher->email, 'otp' => $otp])
            ->assertJsonPath('teacher.needs_username', false);

        $this->postJson('/api/auth/teacher/complete-setup', [
            'setup_token' => $verify->json('setup_token'),
            'password' => 'new-password-1',
            'password_confirmation' => 'new-password-1',
        ])->assertOk();

        $teacher->refresh();
        $this->assertSame('existing_user', $teacher->username);
        $this->assertTrue(Hash::check('new-password-1', $teacher->password));
        $this->assertSame(1, $teacher->tokens()->count());
    }
}
