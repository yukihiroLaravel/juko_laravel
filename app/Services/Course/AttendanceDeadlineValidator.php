<?php

namespace App\Services\Course;

use App\Model\Course;
use Carbon\CarbonImmutable;

class AttendanceDeadlineValidator
{
    /**
     * 受講期限の検証
     * 期限内の場合は true、期限切れの場合は false を返す
     */
    public function __invoke(Course $course): bool
    {
        if (! $course->courseDeadline?->fixed_deadline_end) {
            return true;
        }

        return CarbonImmutable::now()->lessThanOrEqualTo($course->courseDeadline->fixed_deadline_end);
    }
}
