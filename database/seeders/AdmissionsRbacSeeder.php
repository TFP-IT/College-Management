<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Roles, permissions and staff accounts for the admissions module and the CMS.
 * Staff passwords here are for local development only; change them before any shared deployment.
 */
class AdmissionsRbacSeeder extends Seeder
{
    public const PERMISSIONS = [
        'admission.program.manage',
        'admission.application.view',
        'admission.document.verify',
        'admission.assessment.record',
        'admission.decide',
        'admission.payment.view',
        'admission.enroll',
        'admission.report.view',
        'admission.override_capacity',
        'cms.manage',
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $map = [
            'Super Admin' => self::PERMISSIONS,
            'Admission Council Member' => ['admission.application.view', 'admission.assessment.record', 'admission.decide', 'admission.payment.view', 'admission.report.view'],
            'Admissions Officer' => ['admission.application.view', 'admission.document.verify', 'admission.assessment.record', 'admission.payment.view', 'admission.enroll', 'admission.report.view'],
            'Principal' => ['admission.application.view', 'admission.payment.view', 'admission.report.view', 'admission.override_capacity', 'cms.manage'],
            'Student' => [],
            'Guardian' => [],
        ];

        foreach ($map as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($permissions);
        }

        // Roles and permissions are safe anywhere. The accounts below have known passwords, so a live
        // site gets none of them: grant roles to real staff with "admissions:grant-role".
        if (app()->isProduction()) {
            $this->command?->warn('Skipped demo staff accounts: not allowed in production.');

            return;
        }

        $staff = [
            ['email' => 'council@mcc.edu.bd', 'name' => 'Prof. Dr. Council Chair', 'phone' => '01711000001', 'role' => 'Admission Council Member'],
            ['email' => 'officer@mcc.edu.bd', 'name' => 'Admissions Desk Officer', 'phone' => '01711000002', 'role' => 'Admissions Officer'],
            ['email' => 'principal@mcc.edu.bd', 'name' => 'Principal', 'phone' => '01711000003', 'role' => 'Principal'],
            ['email' => 'admin@mcc.edu.bd', 'name' => 'Admin User', 'phone' => '01711000004', 'role' => 'Super Admin'],
        ];

        $passwords = [
            'council@mcc.edu.bd' => 'Council@123',
            'officer@mcc.edu.bd' => 'Officer@123',
            'principal@mcc.edu.bd' => 'Principal@123',
            'admin@mcc.edu.bd' => 'Admin@123',
        ];

        foreach ($staff as $member) {
            $user = User::firstOrCreate(
                ['email' => $member['email']],
                [
                    'name' => $member['name'],
                    'password' => Hash::make($passwords[$member['email']]),
                    'phone_number' => $member['phone'],
                    'role_id' => 3,
                    'user_type' => 'regular',
                    'status' => 1,
                    'school_id' => 1,
                ]
            );
            $user->syncRoles([$member['role']]);
        }
    }
}
