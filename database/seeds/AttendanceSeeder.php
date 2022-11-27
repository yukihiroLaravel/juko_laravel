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
            // 受講生1
            [
                'course_id' => 1,
                'student_id' => 1,
                'progress' => 10,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'course_id' => 2,
                'student_id' => 1,
                'progress' => 20,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'course_id' => 3,
                'student_id' => 1,
                'progress' => 30,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'course_id' => 4,
                'student_id' => 1,
                'progress' => 40,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'course_id' => 5,
                'student_id' => 1,
                'progress' => 50,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'course_id' => 6,
                'student_id' => 1,
                'progress' => 60,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'course_id' => 7,
                'student_id' => 1,
                'progress' => 70,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            // 受講生2
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
