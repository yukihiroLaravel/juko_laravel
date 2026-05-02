<?php

namespace App\Services\Attendance;

use App\Model\Attendance;
use Carbon\CarbonImmutable;

final class ShowService
{
    /**
     * 受講状況詳細に必要な情報（期限切れを除いた受講人数を含む）を取得する
     *
     * @return array{attendance: Attendance, studentsCount: int}
     */
    public function __invoke(Attendance $attendance): array
    {
        $studentsCount = Attendance::where('course_id', $attendance->course_id)
            ->where(function ($query) {
                $query->whereNull('attendance_deadline')
                    ->orWhere('attendance_deadline', '>=', CarbonImmutable::today());
            })
            ->count();

        return [
            'attendance' => $attendance,
            'studentsCount' => $studentsCount,
        ];
    }
}
