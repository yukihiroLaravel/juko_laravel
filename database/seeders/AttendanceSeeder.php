<?php

namespace Database\Seeders;

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
        // Attendance::insert([
        //     [
        //         'course_id' => 1,
        //         'student_id' => 1,
        //         'created_at' => Carbon::now(),
        //         'updated_at' => Carbon::now(),
        //         'attendance_deadline' => null,
        //     ],
        //     [
        //         'course_id' => 1,
        //         'student_id' => 2,
        //         'created_at' => Carbon::now(),
        //         'updated_at' => Carbon::now(),
        //         'attendance_deadline' => null,
        //     ],
        //     [
        //         'course_id' => 6,
        //         'student_id' => 1,
        //         'created_at' => Carbon::now(),
        //         'updated_at' => Carbon::now(),
        //         'attendance_deadline' => null,
        //     ],
        //     [
        //         'course_id' => 2,
        //         'student_id' => 1,
        //         'created_at' => Carbon::now(),
        //         'updated_at' => Carbon::now(),
        //         'attendance_deadline' => Carbon::now()->addYear(),
        //     ],
        // ]);
    }
}
