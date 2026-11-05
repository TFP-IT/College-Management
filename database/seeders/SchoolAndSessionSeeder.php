<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SchoolAndSessionSeeder extends Seeder
{
    public function run()
    {
        DB::table('sessions')->updateOrInsert(
            ['id' => 1],
            [
                'session_title' => '2026',
                'status' => 1,
                'school_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('schools')->updateOrInsert(
            ['id' => 1],
            [
                'title' => 'Maestro Crown School & College',
                'email' => 'mccsavar@gmail.com',
                'phone' => '8801309136876',
                'address' => '107/2, Dogormura, Radio Colony, Dhaka Aricha Highway, Savar, Bangladesh, 1343',
                'school_info' => 'Maestro Crown School and College is the first digitized college in Savar. Providing excellence in education .',
                'status' => 1,
                'running_session' => 1,
                'school_currency' => 'BDT',
                'currency_position' => 'left',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
