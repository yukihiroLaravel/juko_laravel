<?php

namespace App\Services\Course;

use App\Model\Course;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;

class AttendanceDeadlineValidator
{
    /**
     * 受講期限のバリデーション
     */
    public function __invoke(Course $course): void
    {
        if (
            $course->attendance_deadline &&
            CarbonImmutable::now()->gte($course->attendance_deadline->endOfDay())
        ) {
            throw new AuthorizationException('The course has expired.');
        }
    }

    /**
     * 受講期限が切れているかどうかを判定
     */
    public function isExpired(Course $course): bool
    {
        return $course->attendance_deadline &&
               CarbonImmutable::now()->gte($course->attendance_deadline->endOfDay());
    }
}
