<?php

namespace App\Services\Attendance;

use App\Model\Attendance;
use App\Model\Student;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class ExpiringService
{
    /**
     * 近日期限切れ予定の受講生を取得する
     *
     * @param  int  $course_id  講座ID
     * @param  array<int>  $thresholds  期限切れまでの日数の配列
     */
    public function __invoke(int $course_id, array $thresholds): Collection
    {
        sort($thresholds);

        $now = CarbonImmutable::now();
        $result = collect();
        $previousDays = 0;

        foreach ($thresholds as $days) {
            $from = $now->addDays($previousDays);
            $to = $now->addDays($days);

            $students = Student::whereHas('attendances', fn ($q) => $q
                ->where('course_id', $course_id)
                ->where('attendance_deadline', '>', $from)
                ->where('attendance_deadline', '<=', $to)
            )
            ->select([
                'students.id',
                'students.last_name',
                'students.first_name',
                'students.email',
            ])
            ->addSelect([
                'attendance_deadline' => Attendance::select('attendance_deadline')
                    ->whereColumn('student_id', 'students.id')
                    ->where('course_id', $course_id)
                    ->whereNull('deleted_at') 
                    ->orderBy('attendance_deadline', 'asc')
                    ->limit(1),
            ])
            ->orderBy('attendance_deadline', 'asc')
            ->get();

            $result->push([
                'days' => $days,
                'students' => $students,
            ]);

            $previousDays = $days;
        }

        return $result;
    }
}
