<?php

namespace App\Services\Course;

use App\Model\Course;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;

class AttendanceDeadlineValidator
{
    /** 期限切れなら例外を投げる（期限当日23:59:59までは許可） */
    public function __invoke(Course $course): void
    {
        if (
            $course->attendance_deadline &&
            CarbonImmutable::now()->gte($course->attendance_deadline->endOfDay())
        ) {
            throw new AuthorizationException('The course has expired.');
        }
    }

    /** 判定だけ欲しい場合 */
    public function isExpired(Course $course): bool
    {
        return $course->attendance_deadline &&
               CarbonImmutable::now()->gte($course->attendance_deadline->endOfDay());
    }
}
