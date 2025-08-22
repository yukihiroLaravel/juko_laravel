<?php

namespace App\Services\Course;

use App\Model\Course;
use Carbon\CarbonImmutable;

class AttendanceDeadlineValidator
{
    /**
     * 受講期限が切れているかどうかを判定
     */
    public function __invoke(Course $course): bool
    {
        return !(
            $course->attendance_deadline &&
            CarbonImmutable::now()->gte($course->attendance_deadline->endOfDay())
        );
    }
}