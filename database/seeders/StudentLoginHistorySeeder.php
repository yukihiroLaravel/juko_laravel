<?php

namespace Database\Seeders;

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

        StudentLoginHistory::insert([
            // 生徒1のログイン履歴（直近30日以内に複数回）
            [
                'student_id' => 1,
                'logged_in_at' => $now->copy()->subDays(1),
                'created_at' => $now->copy()->subDays(1),
                'updated_at' => $now->copy()->subDays(1),
            ],
            [
                'student_id' => 1,
                'logged_in_at' => $now->copy()->subDays(5),
                'created_at' => $now->copy()->subDays(5),
                'updated_at' => $now->copy()->subDays(5),
            ],
            [
                'student_id' => 1,
                'logged_in_at' => $now->copy()->subDays(10),
                'created_at' => $now->copy()->subDays(10),
                'updated_at' => $now->copy()->subDays(10),
            ],
            // 生徒2のログイン履歴（直近30日以内）
            [
                'student_id' => 2,
                'logged_in_at' => $now->copy()->subDays(2),
                'created_at' => $now->copy()->subDays(2),
                'updated_at' => $now->copy()->subDays(2),
            ],
            [
                'student_id' => 2,
                'logged_in_at' => $now->copy()->subDays(15),
                'created_at' => $now->copy()->subDays(15),
                'updated_at' => $now->copy()->subDays(15),
            ],
            // 生徒3のログイン履歴（30日以上前のデータも含め、集計テスト用）
            [
                'student_id' => 3,
                'logged_in_at' => $now->copy()->subDays(3),
                'created_at' => $now->copy()->subDays(3),
                'updated_at' => $now->copy()->subDays(3),
            ],
            [
                'student_id' => 3,
                'logged_in_at' => $now->copy()->subDays(31),
                'created_at' => $now->copy()->subDays(31),
                'updated_at' => $now->copy()->subDays(31),
            ],
        ]);
    }
}
