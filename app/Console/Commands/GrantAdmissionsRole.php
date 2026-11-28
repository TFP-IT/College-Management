<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class GrantAdmissionsRole extends Command
{
    /** Roles that open the office portal. They are created on first use. */
    public const OFFICE_ROLES = ['Principal', 'HR Officer', 'Discipline Officer', 'Safeguarding Officer', 'Counsellor', 'Transport Coordinator',
        'Sports Coordinator', 'Cultural Coordinator', 'Career Coordinator', 'Innovation Coordinator', 'Registrar', 'Operations Manager'];

    protected $signature = 'admissions:grant-role {email : Email of an existing account} {role : For example "Principal" or "Admissions Officer"}';

    protected $description = 'Give an existing staff account an admissions / CMS role';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();
        $name = $this->argument('role');
        $role = in_array($name, self::OFFICE_ROLES, true)
            ? Role::findOrCreate($name, 'web')
            : Role::where('name', $name)->where('guard_name', 'web')->first();

        if (!$user || !$role) {
            $this->error(!$user ? 'No account has that email.' : 'Unknown role. Available: ' . Role::pluck('name')->merge(self::OFFICE_ROLES)->unique()->implode(', '));

            return self::FAILURE;
        }

        $user->assignRole($role);
        $this->info("{$user->name} now has the role \"{$role->name}\".");

        return self::SUCCESS;
    }
}
