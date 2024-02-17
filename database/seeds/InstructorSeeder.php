<?php

use App\Model\Instructor;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class InstructorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Instructor::insert([
            [
                'nick_name' => 'Yamada',
                'last_name' => '山田',
                'first_name' => '太郎',
                'email' => 'test_instructor@example.com',
                'password' => Hash::make('password'),
                'profile_image' => 'instructor/default.png',
                'type' => 'manager',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'nick_name' => 'Hanako',
                'last_name' => '山本',
                'first_name' => '花子',
                'email' => 'test_instructor2@example.com',
                'password' => Hash::make('password'),
                'type' => 'instructor',
                'profile_image' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'nick_name' => 'Suzuki',
                'last_name' => '鈴木',
                'first_name' => '次郎',
                'email' => 'test_instructor3@example.com',
                'password' => Hash::make('password'),
                'type' => 'instructor',
                'profile_image' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'nick_name' => 'Tanaka',
                'last_name' => '田中',
                'first_name' => '三郎',
                'email' => 'test_instructor4@example.com',
                'password' => Hash::make('password'),
                'type' => 'manager',
                'profile_image' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        ]);
    }
}
