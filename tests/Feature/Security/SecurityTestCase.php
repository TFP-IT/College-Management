<?php

namespace Tests\Feature\Security;

use App\Models\Admission;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

abstract class SecurityTestCase extends TestCase
{
    use DatabaseTransactions;

    protected int $schoolId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->schoolId = (int) DB::table('schools')->insertGetId(['title' => 'Security Test School', 'status' => 1, 'created_at' => now(), 'updated_at' => now()]);
    }

    protected function user(int $role, array $extra = []): User
    {
        return User::create(array_merge([
            'name' => 'Security ' . uniqid(),
            'email' => uniqid('sec', true) . '@example.test',
            'role_id' => $role,
            'school_id' => $this->schoolId,
            'status' => 1,
            'password' => bcrypt('original-secret'),
            'user_information' => json_encode(['gender' => '', 'blood_group' => '', 'birthday' => '', 'phone' => '', 'address' => '', 'photo' => '']),
        ], $extra));
    }

    protected function admission(User $student, ?User $guardian = null, string $status = 'pending'): Admission
    {
        return Admission::create([
            'user_id' => $student->id,
            'guardian_user_id' => $guardian?->id,
            'application_no' => 'SEC-' . uniqid(),
            'name' => $student->name,
            'class_name' => 'Class 7',
            'department' => 'General',
            'status' => $status,
            'mobile' => '01700000000',
            'email' => $student->email,
            'father_name' => 'Father',
            'mother_name' => 'Mother',
            'guardian_name' => 'Guardian',
            'relation_with_guardian' => 'Father',
            'guardian_mobile' => '01700000001',
            'present_address' => 'Savar, Dhaka',
        ]);
    }
}
