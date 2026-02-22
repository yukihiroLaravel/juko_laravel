<?php

namespace Database\Seeders;

use App\Model\Student;
use App\Model\StudentLoginHistory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class StudentLoginHistorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $now = Carbon::now();

        $studentIds = Student::query()->limit(3)->pluck('id');

        // 取得できた件数が足りない場合はスキップ
        if ($studentIds->count() < 3) {
            return;
        }

        StudentLoginHistory::insert([
            ['student_id' => $studentIds[0], 'logged_in_at' => $now->copy()->subDays(1), 'created_at' => $now->copy()->subDays(1), 'updated_at' => $now->copy()->subDays(1)],
            ['student_id' => $studentIds[0], 'logged_in_at' => $now->copy()->subDays(5), 'created_at' => $now->copy()->subDays(5), 'updated_at' => $now->copy()->subDays(5)],
            ['student_id' => $studentIds[0], 'logged_in_at' => $now->copy()->subDays(10), 'created_at' => $now->copy()->subDays(10), 'updated_at' => $now->copy()->subDays(10)],
            ['student_id' => $studentIds[1], 'logged_in_at' => $now->copy()->subDays(2), 'created_at' => $now->copy()->subDays(2), 'updated_at' => $now->copy()->subDays(2)],
            ['student_id' => $studentIds[1], 'logged_in_at' => $now->copy()->subDays(15), 'created_at' => $now->copy()->subDays(15), 'updated_at' => $now->copy()->subDays(15)],
            ['student_id' => $studentIds[2], 'logged_in_at' => $now->copy()->subDays(3), 'created_at' => $now->copy()->subDays(3), 'updated_at' => $now->copy()->subDays(3)],
            ['student_id' => $studentIds[2], 'logged_in_at' => $now->copy()->subDays(31), 'created_at' => $now->copy()->subDays(31), 'updated_at' => $now->copy()->subDays(31)],
        ]);
    }
}
