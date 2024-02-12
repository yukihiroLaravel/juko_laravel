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
        for ($i = 1; $i <= 30; $i++) {
            // 10個単位でcreated_atをずらして
            $created_at = Carbon::now()->subDays(30 - $i);

            Attendance::insert([
                'course_id' => 1,
                'student_id' => $i,
                'progress' => 10,
                'created_at' => $created_at,
                'updated_at' => $created_at,
            ]);
        }
    }
}
