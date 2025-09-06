<?php

namespace App\Services\Course;

use App\Model\Attendance;
use Carbon\CarbonImmutable;

class AttendanceDeadlineValidator
{
    /**
     * 受講期限の検証
     * 期限内の場合は true、期限切れの場合は false を返す
     */
    public function __invoke(Attendance $attendance): bool
    {
        if ($attendance->attendance_deadline === null) {
            return true;
        }

        return CarbonImmutable::now()->lessThanOrEqualTo($attendance->attendance_deadline);
    }
}
