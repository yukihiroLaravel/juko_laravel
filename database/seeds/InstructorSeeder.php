<?php

namespace  App\Database\Seeds;

use App\Model\Instructor;
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
        Instructor::create(
            [
                'nick_name' => 'ニックネーム',
                'last_name' => '山田',
                'first_name' => '太郎',
                'email' => 'test_instructor@example.com',
                'password' => Hash::make('password')
            ]
        );
    }
}
