<?php

use App\Model\LessonAttendance;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class LessonAttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        LessonAttendance::insert([
            [
                'lesson_id' => 1,
                'attendance_id' => 1,
                'status' => LessonAttendance::STATUS_COMPLETED_ATTENDANCE,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'lesson_id' => 2,
                'attendance_id' => 1,
                'status' => LessonAttendance::STATUS_COMPLETED_ATTENDANCE,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'lesson_id' => 3,
                'attendance_id' => 1,
                'status' => LessonAttendance::STATUS_IN_ATTENDANCE,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'lesson_id' => 4,
                'attendance_id' => 1,
                'status' => LessonAttendance::STATUS_BEFORE_ATTENDANCE,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'lesson_id' => 5,
                'attendance_id' => 1,
                'status' => LessonAttendance::STATUS_BEFORE_ATTENDANCE,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'lesson_id' => 6,
                'attendance_id' => 1,
                'status' => LessonAttendance::STATUS_BEFORE_ATTENDANCE,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'lesson_id' => 7,
                'attendance_id' => 2,
                'status' => LessonAttendance::STATUS_BEFORE_ATTENDANCE,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'lesson_id' => 8,
                'attendance_id' => 2,
                'status' => LessonAttendance::STATUS_BEFORE_ATTENDANCE,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}
