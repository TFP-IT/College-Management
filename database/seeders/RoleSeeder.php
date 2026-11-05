<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            ['role_id' => 1, 'name' => 'superadmin'],
            ['role_id' => 2, 'name' => 'admin'],
            ['role_id' => 3, 'name' => 'teacher'],
            ['role_id' => 4, 'name' => 'accountant'],
            ['role_id' => 5, 'name' => 'librarian'],
            ['role_id' => 6, 'name' => 'parent'],
            ['role_id' => 7, 'name' => 'student'],
            ['role_id' => 8, 'name' => 'user'],
            ['role_id' => 9, 'name' => 'alumni'],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['role_id' => $role['role_id']],
                ['name' => $role['name'], 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
