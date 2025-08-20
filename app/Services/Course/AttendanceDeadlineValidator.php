<?php

namespace App\Services\Course;

use App\Model\Course;
use Illuminate\Support\Carbon;

class AttendanceDeadlineValidator
{
    /**
     * 受講期限チェック
     *
     * @param  Course  $course
     * @return bool
     */
    public function __invoke(Course $course): bool
    {
        if (!$course->attendance_deadline_end) {
            return true;
        }

        return now()->lessThanOrEqualTo($course->attendance_deadline_end);
    }
}