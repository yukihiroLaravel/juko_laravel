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
                'status' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'lesson_id' => 2,
                'attendance_id' => 1,
                'status' => 2,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'lesson_id' => 3,
                'attendance_id' => 1,
                'status' => 2,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            // [
            //     'lesson_id' => 4,
            //     'attendance_id' => 2,
            //     'status' => 2,
            //     'created_at' => Carbon::now(),
            //     'updated_at' => Carbon::now(),
            // ],
        ]);
    }
}
