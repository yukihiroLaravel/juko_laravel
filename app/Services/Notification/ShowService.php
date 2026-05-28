<?php

namespace App\Services\Notification;

use App\Model\Attendance;
use App\Model\Notification;
use App\Model\Student;
use Illuminate\Auth\Access\AuthorizationException;

class ShowService
{
    /**
     * お知らせ詳細取得
     *
     * @return array {notification: Notification, attendance: Attendance}
     */
    public function __invoke(Student $student, int $notificationId): array
    {
        /** @var Notification $notification */
        $notification = Notification::public()
            ->with('course', 'instructor')
            ->findOrFail($notificationId);

        /** @var Attendance $attendance */
        $attendance = Attendance::where('student_id', $student->id)
            ->where('course_id', $notification->course_id)
            ->firstOrFail();

        if ($attendance->isExpired()) {
            // 受講期限切れ
            throw new AuthorizationException('The course has expired.');
        }

        return [
            'notification' => $notification,
            'attendance' => $attendance,
        ];
    }
}
