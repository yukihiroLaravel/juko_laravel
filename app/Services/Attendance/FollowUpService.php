<?php

namespace App\Services\Attendance;

use App\Model\Student;
use App\Model\StudentLoginHistory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class FollowUpService
{
    /**
     * 要フォロー受講生を取得する
     *
     * @param int $course_id 講座ID
     * @param int $days 最終ログインからの日数
     */
    public function __invoke(int $course_id, int $days): Collection
    {
        return Student::whereHas('attendances', fn($q) => $q->where('course_id', $course_id))
            ->select([
                'students.id',
                'students.last_name',
                'students.first_name',
                'students.email',
            ])
            ->addSelect([
                'latest_login_at' => StudentLoginHistory::select('logged_in_at')
                    ->whereColumn('student_id', 'students.id')
                    ->latest('logged_in_at')
                    ->limit(1),
            ])
            ->having(
                DB::raw('IFNULL(DATEDIFF(NOW(), (SELECT MAX(logged_in_at) FROM student_login_histories WHERE student_id = students.id)), 99999)'),
                '>=',
                $days
            )
            ->orderByRaw('latest_login_at IS NULL DESC, latest_login_at ASC')
            ->get();
    }
}