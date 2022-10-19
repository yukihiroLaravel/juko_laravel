<?php

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
            'name' => 'Test',
            'email' => 'test@example.com',
            'password' => Hash::make('password')
            ]
        );
    }
}
