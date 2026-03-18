<?php

namespace App\Services\Attendance;

use App\Model\Student;
use App\Model\StudentLoginHistory;
use Carbon\CarbonImmutable;

final class FollowUpService
{
    /**
     * 要フォロー受講生を取得する
     *
     * @param int $course_id 講座ID
     * @param int $days 最終ログインからの日数
     * @return array フォローが必要な受講生のリスト
     */
    public function getFollowUpStudents(int $course_id, int $days): array
    {
        $now = CarbonImmutable::now();

        $students = Student::whereHas('attendances', fn($q) => $q->where('course_id', $course_id))
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
            ->get()
            ->map(function ($student) use ($now) {
                $lastLogin = $student->latest_login_at;
                $daysSince = $lastLogin
                    ? (int) CarbonImmutable::parse($lastLogin)->diffInDays($now)
                    : PHP_INT_MAX;

                return [
                    'student_id'       => $student->id,
                    'name'             => $student->last_name . ' ' . $student->first_name,
                    'email'            => $student->email,
                    'last_login_at'    => $lastLogin
                        ? CarbonImmutable::parse($lastLogin)->toIso8601String()
                        : null,
                    'days_since_login' => $daysSince,
                ];
            })
            ->filter(fn($s) => $s['days_since_login'] >= $days)
            ->sortByDesc('days_since_login')
            ->map(fn($s) => array_merge($s, [
                'days_since_login' => $s['days_since_login'] === PHP_INT_MAX ? null : $s['days_since_login'], // 最後にnullへ変換
            ]))
            ->values();

        return ['students' => $students];
    }
}