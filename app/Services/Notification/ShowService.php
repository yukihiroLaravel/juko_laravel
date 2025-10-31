<?php

namespace App\Services\Notification;

use App\Model\Attendance;
use App\Model\Notification;
use App\Model\Student;
use Illuminate\Auth\Access\AuthorizationException;

class ShowService
{
    /**
     * お知らせ詳細 + 受講期限チェック（最小）
     * 期限判定は Attendance::isExpired() に委譲
     * 期限切れ時: 403 "The course has expired."
     */
    public function __invoke(Student $student, int $notificationId): Notification
    {
        /** @var Notification $notification */
        $notification = Notification::public()
            ->with('course') // 不要なら外してOK
            ->findOrFail($notificationId);

        /** @var Attendance|null $attendance */
        $attendance = Attendance::where('student_id', $student->id)
            ->where('course_id', $notification->course_id)
            ->first();

        // 受講していないとき
        if (! $attendance) {
            throw new AuthorizationException('You are not enrolled in the course.');
        }

        // 期限切れのとき
        if ($attendance->isExpired()) {
            throw new AuthorizationException('The course has expired.');
        }

        return $notification;
    }
}
