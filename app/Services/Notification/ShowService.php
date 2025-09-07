<?php

namespace App\Services\Notification;

use App\Model\Attendance;
use App\Model\CourseDeadline;
use App\Model\Notification;
use App\Model\Student;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;

class ShowService
{
    /**
     * お知らせ詳細 + 受講期限チェック
     * 基本：Attendance::isExpired() を使う
     * 例外：attendance_deadline が NULL の古いデータのみ course_deadlines でフォールバック
     */
    public function __invoke(Student $student, int $notificationId): Notification
    {
        /** @var Notification $notification */
        $notification = Notification::public()
            ->with('course')
            ->findOrFail($notificationId);

        /** @var Attendance|null $attendance */
        $attendance = Attendance::where('student_id', $student->id)
            ->where('course_id', $notification->course_id)
            ->first();

        if (! $attendance) {
            throw new AuthorizationException('Forbidden, not allowed to this notification.');
        }

        // 1) 通常パス：個別期限だけを見る
        if ($attendance->attendance_deadline !== null) {
            if ($attendance->isExpired()) {
                throw new AuthorizationException('The course has expired.');
            }
            return $notification;
        }

        // 2) フォールバック：個別期限がNULL（レガシーデータ）だけ講座設定を見る
        $exp = $this->fallbackExpiry(
            (string) ($notification->course->deadline_type ?? ''),
            $notification->course_id,
            $student->id
        );

        if ($exp && CarbonImmutable::now()->gte($exp)) {
            throw new AuthorizationException('The course has expired.');
        }

        return $notification;
    }

    private function fallbackExpiry(string $mode, int $courseId, int $studentId): ?CarbonImmutable
    {
        $mode = strtolower(trim($mode) ?: 'none');
        $deadline = CourseDeadline::where('course_id', $courseId)->first();

        if ($mode === 'fixed') {
            return $deadline?->fixed_date ? $this->toCI($deadline->fixed_date)->endOfDay() : null;
        }

        if ($mode === 'relative') {
            $days = $deadline?->relative_days;
            if ($days !== null) {
                $startedAt = Attendance::where('student_id', $studentId)
                    ->where('course_id', $courseId)
                    ->oldest('created_at')
                    ->value('created_at');
                return $startedAt
                    ? CarbonImmutable::parse($startedAt)->addDays((int)$days)->endOfDay()
                    : null;
            }
            return null;
        }

        // none：基本は期限なし。ただし course_deadlinesに値があれば採用
        if ($deadline?->fixed_date) {
            return $this->toCI($deadline->fixed_date)->endOfDay();
        }
        if ($deadline?->relative_days !== null) {
            $startedAt = Attendance::where('student_id', $studentId)
                ->where('course_id', $courseId)
                ->oldest('created_at')
                ->value('created_at');
            return $startedAt
                ? CarbonImmutable::parse($startedAt)->addDays((int)$deadline->relative_days)->endOfDay()
                : null;
        }

        return null;
    }

    /** @param \DateTimeInterface|string $v */
    private function toCI($v): CarbonImmutable
    {
        return $v instanceof \DateTimeInterface
            ? CarbonImmutable::instance($v)
            : CarbonImmutable::parse((string)$v);
    }
}
