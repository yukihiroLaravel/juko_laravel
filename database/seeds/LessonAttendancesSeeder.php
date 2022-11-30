<?php

use App\Model\LessonAttendance;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class LessonAttendancesSeeder extends Seeder
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
                'status' => 10,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'lesson_id' => 2,
                'attendance_id' => 1,
                'status' => 20,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'lesson_id' => 3,
                'attendance_id' => 1,
                'status' => 10,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'lesson_id' => 4,
                'attendance_id' => 1,
                'status' => 30,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'lesson_id' => 5,
                'attendance_id' => 1,
                'status' => 40,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'lesson_id' => 6,
                'attendance_id' => 1,
                'status' => 20,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'lesson_id' => 7,
                'attendance_id' => 1,
                'status' => 10,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'lesson_id' => 8,
                'attendance_id' => 1,
                'status' => 20,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'lesson_id' => 9,
                'attendance_id' => 1,
                'status' => 10,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'lesson_id' => 10,
                'attendance_id' => 1,
                'status' => 30,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        ]);
    }
}
