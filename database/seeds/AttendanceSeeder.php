<?php

use App\Model\Attendance;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Attendance::insert([
            [
                'course_id' => 1,
                'student_id' => 1,
                'progress' => 70,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'course_id' => 1,
                'student_id' => 2,
                'progress' => 100,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'course_id' => 1,
                'student_id' => 1,
                'progress' => 70,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'course_id' => 1,
                'student_id' => 2,
                'progress' => 100,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],[
                'course_id' => 1,
                'student_id' => 1,
                'progress' => 70,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'course_id' => 1,
                'student_id' => 2,
                'progress' => 100,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],[
                'course_id' => 1,
                'student_id' => 1,
                'progress' => 70,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'course_id' => 1,
                'student_id' => 2,
                'progress' => 100,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],[
                'course_id' => 1,
                'student_id' => 1,
                'progress' => 70,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'course_id' => 1,
                'student_id' => 2,
                'progress' => 100,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}
