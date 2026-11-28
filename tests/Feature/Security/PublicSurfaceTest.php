<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class PublicSurfaceTest extends SecurityTestCase
{
    private function signup(array $data)
    {
        return $this->postJson('/api/admission/submit', $data + ['role' => 'guardian', 'is_school' => true]);
    }

    public function test_signup_with_a_registered_phone_changes_nothing_and_gives_no_token(): void
    {
        $guardian = $this->user(6, ['phone_number' => '01811111111', 'name' => 'Real Guardian']);

        $response = $this->signup(['fullName' => 'Intruder', 'phone' => '01811111111', 'password' => 'taken-over']);

        $response->assertStatus(422)->assertJsonMissingPath('token')->assertJsonValidationErrors('phone');
        $guardian->refresh();
        $this->assertSame('Real Guardian', $guardian->name);
        $this->assertTrue(Hash::check('original-secret', $guardian->password));
        $this->assertSame(0, $guardian->tokens()->count());
    }

    public function test_signup_cannot_reuse_another_accounts_email(): void
    {
        $admin = $this->user(2);

        $this->signup(['fullName' => 'Someone', 'phone' => '01822222222', 'email' => $admin->email, 'password' => 'secret123'])
            ->assertStatus(422)->assertJsonValidationErrors('email');

        $this->assertFalse(User::where('phone_number', '01822222222')->exists());
    }

    public function test_a_new_guardian_can_still_register_and_the_chosen_password_is_not_echoed(): void
    {
        $this->signup(['fullName' => 'New Guardian', 'phone' => '01833333333', 'password' => 'secret123'])
            ->assertCreated()
            ->assertJsonPath('data.password', null)
            ->assertJsonStructure(['token']);
    }

    public function test_installer_and_debug_routes_are_closed(): void
    {
        foreach (['/install/step0', '/install/finalizing_setup', '/install/install', '/install_ended', '/clear-cache', '/test-pdf'] as $url) {
            $this->get($url)->assertNotFound();
        }

        $before = User::where('role_id', 1)->count();
        $this->post('/install/finalizing_setup', [
            'current_session' => '2026', 'system_name' => 'x', 'timezone' => 'Asia/Dhaka',
            'superadmin_name' => 'x', 'superadmin_email' => 'intruder@example.test',
            'superadmin_password' => 'x', 'superadmin_phone' => 'x', 'superadmin_address' => 'x',
        ])->assertNotFound();

        $this->assertSame($before, User::where('role_id', 1)->count());
    }

    public function test_installer_stays_closed_once_a_superadmin_exists_even_if_switched_on(): void
    {
        config(['security.installer_enabled' => true]);
        $this->user(1);

        $this->get('/install/finalizing_setup')->assertNotFound();
    }

    public function test_repeated_wrong_passwords_are_slowed_down(): void
    {
        $guardian = $this->user(6, ['phone_number' => '01844444444']);
        $attempt = fn () => $this->postJson('/api/app_login', ['role' => 'guardian', 'login_id' => $guardian->phone_number, 'password' => 'wrong']);

        for ($i = 0; $i < 5; $i++) {
            $attempt()->assertStatus(401);
        }

        $attempt()->assertStatus(429);
    }
}
