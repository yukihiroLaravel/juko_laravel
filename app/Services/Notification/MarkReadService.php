<?php

namespace App\Services\Notification;

use App\Enums\Notification\TypeEnum;
use App\Enums\Course\DeadlineTypeEnum;
use App\Model\Notification;
use App\Model\Student;
use App\Model\Attendance;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;

class MarkReadService
{
    /**
     * お知らせ既読処理
     */
    public function __invoke(Student $student, int $notificationId): void
    {
        $notification = Notification::where('id', $notificationId)
            ->where('type', TypeEnum::ONCE)
            ->with(['students', 'course.courseDeadline'])
            ->firstOrFail();
        
            $course = $notification->course;

        match (DeadlineTypeEnum::tryFrom($course->deadline_type)) {
            DeadlineTypeEnum::FIXED_DATE => $this->checkFixedDateExpired($course),
            DeadlineTypeEnum::RELATIVE_DAYS => $this->checkRelativeDateExpired($student, $course->id),
            default => null,
        };

        // ユーザが確認したお知らせを登録(既読登録)
        if (! $notification->students->contains($student->id)) {
            $notification->students()->attach($student->id);
        }
    }

    /**
     * 固定期限タイプの期限切れチェック
     */
    private function checkFixedDateExpired($course): void
    {
        $fixedDate = $course->courseDeadline?->fixed_date;
        if ($fixedDate && CarbonImmutable::now()->gte($fixedDate->endOfDay())) {
            throw new AuthorizationException('The course has expired.');
        }
    }

    /**
     * 相対日数タイプの期限切れチェック
     */
    private function checkRelativeDateExpired(Student $student, int $courseId): void
    {
        $deadline = Attendance::where('student_id', $student->id)
            ->where('course_id', $courseId)
            ->value('attendance_deadline');

        if ($deadline && CarbonImmutable::now()->gte(CarbonImmutable::parse($deadline)->endOfDay())) {
            throw new AuthorizationException('The course has expired.');
        }
    }
}
