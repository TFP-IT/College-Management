<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = [
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@gmail.com',
                'role_id' => 1,
                'school_id' => null,
                'status' => 1,
                'password' => Hash::make('12345678'),
                'user_information' => json_encode(['gender' => 'Male', 'blood_group' => 'a+', 'birthday' => 1777358304, 'phone' => '01616122333', 'address' => 'Badda, DHAKA', 'photo' => 'user.png']),
            ],
            [
                'name' => 'Admin User',
                'email' => 'admin@gmail.com',
                'role_id' => 2,
                'school_id' => 1,
                'status' => 1,
                'password' => Hash::make('12345678'),
                'user_information' => json_encode(['gender' => 'Male', 'blood_group' => 'a+', 'birthday' => 1777358439, 'phone' => '01616122333', 'address' => 'Badda, DHAKA', 'photo' => 'user.png']),
            ],
            [
                'name' => 'Teacher User',
                'email' => 'teacher@gmail.com',
                'role_id' => 3,
                'school_id' => 1,
                'status' => 1,
                'password' => Hash::make('12345678'),
                'user_information' => json_encode(['gender' => 'Male', 'blood_group' => 'a+', 'birthday' => 1777358439, 'phone' => '01616122333', 'address' => 'Badda, DHAKA', 'photo' => 'user.png']),
            ],
            [
                'name' => 'Accountant User',
                'email' => 'accountant@gmail.com',
                'role_id' => 4,
                'school_id' => 1,
                'status' => 1,
                'password' => Hash::make('12345678'),
                'user_information' => json_encode(['gender' => 'Male', 'blood_group' => 'a+', 'birthday' => 1777358439, 'phone' => '01616122333', 'address' => 'Badda, DHAKA', 'photo' => 'user.png']),
            ],
            [
                'name' => 'Librarian User',
                'email' => 'librarian@gmail.com',
                'role_id' => 5,
                'school_id' => 1,
                'status' => 1,
                'password' => Hash::make('12345678'),
                'user_information' => json_encode(['gender' => 'Male', 'blood_group' => 'a+', 'birthday' => 1777358439, 'phone' => '01616122333', 'address' => 'Badda, DHAKA', 'photo' => 'user.png']),
            ],
            [
                'name' => 'Parent User',
                'email' => 'parent@gmail.com',
                'role_id' => 6,
                'school_id' => 1,
                'status' => 1,
                'password' => Hash::make('12345678'),
                'user_information' => json_encode(['gender' => 'Male', 'blood_group' => 'a+', 'birthday' => 1777358439, 'phone' => '01616122333', 'address' => 'Badda, DHAKA', 'photo' => 'user.png']),
            ],
            [
                'name' => 'Student User',
                'email' => 'student@gmail.com',
                'role_id' => 7,
                'school_id' => 1,
                'status' => 1,
                'password' => Hash::make('12345678'),
                'user_information' => json_encode(['gender' => 'Male', 'blood_group' => 'a+', 'birthday' => 1777358439, 'phone' => '01616122333', 'address' => 'Badda, DHAKA', 'photo' => 'user.png']),
            ],
            [
                'name' => 'Driver User',
                'email' => 'driver@gmail.com',
                'role_id' => 8,
                'school_id' => 1,
                'status' => 1,
                'password' => Hash::make('12345678'),
                'user_information' => json_encode(['gender' => 'Male', 'blood_group' => 'a+', 'birthday' => 1777358439, 'phone' => '01616122333', 'address' => 'Badda, DHAKA', 'photo' => 'user.png']),
            ],
            [
                'name' => 'Alumni User',
                'email' => 'alumni@gmail.com',
                'role_id' => 9,
                'school_id' => 1,
                'status' => 1,
                'password' => Hash::make('12345678'),
                'user_information' => json_encode(['gender' => 'Male', 'blood_group' => 'a+', 'birthday' => 1777358439, 'phone' => '01616122333', 'address' => 'Badda, DHAKA', 'photo' => 'user.png']),
            ],
        ];

        foreach ($users as $value) {
            User::updateOrCreate(
                ['email' => $value['email']],
                $value
            );
        }
    }
}
