<?php
namespace App\Services;

use App\Model\Course;
use App\Model\CourseDeadline;
use Illuminate\Support\Carbon;

class CourseDeadlineService
{
    public function expiryAt(
        Course $course,
        ?Carbon $startedAt,
        ?CourseDeadline $deadline = null
    ): ?Carbon {
        $mode = strtolower((string) ($course->deadline_type ?? ''));

        if ($mode === 'fixed') {
            $date = $deadline?->fixed_date;
            if ($date instanceof \DateTimeInterface) return Carbon::instance($date)->endOfDay();
            if (!empty($date)) return Carbon::parse($date)->endOfDay();
            return null;
        }

        if ($mode === 'relative') {
            $days = $deadline?->relative_days;
            return (!is_null($days) && $startedAt)
                ? (clone $startedAt)->addDays((int)$days)->endOfDay()
                : null;
        }

        // ★ none でも、course_deadlines に値が入っていればそれを使う（attendance_deadline は使わない）
        if ($mode === 'none' || $mode === '') {
            if ($deadline) {
                if (!is_null($deadline->relative_days) && $startedAt) {
                    return (clone $startedAt)->addDays((int)$deadline->relative_days)->endOfDay();
                }
                if (!empty($deadline->fixed_date)) {
                    return Carbon::parse($deadline->fixed_date)->endOfDay();
                }
            }
            return null; // 本当に何も設定が無ければ期限なし
        }

        return null;
    }

    public function isExpired(
        Course $course,
        ?Carbon $startedAt,
        ?CourseDeadline $deadline = null,
        ?Carbon $now = null
    ): bool {
        $expiry = $this->expiryAt($course, $startedAt, $deadline);
        if ($expiry === null) return false;
        $now ??= now();
        return $now->greaterThan($expiry);
    }
}
