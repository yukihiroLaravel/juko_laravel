<?php

use App\Model\Student;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class StudentAuthorizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('student_authorization')->insert([
            'student_id' => 1,
            'number_of_attempts' => 1,
            'authentication_code' => 2468,
            'verification_code_validity_period' => Carbon::now(),
        ]);
    }
}
