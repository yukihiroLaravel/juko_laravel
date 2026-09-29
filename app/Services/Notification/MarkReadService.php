<?php

namespace App\Services\Notification;

use App\Enums\Notification\TypeEnum;
use App\Model\Attendance;
use App\Model\Notification;
use App\Model\Student;
use App\Model\ViewedOnceNotification;
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
            ->firstOrFail();

        // 該当生徒の受講期限(attendance_deadline)を取得
        $attendance = Attendance::where('student_id', $student->id)
            ->where('course_id', $notification->course_id)
            ->firstOrFail();

        if ($attendance->isExpired()) {
            throw new AuthorizationException('The course has expired.');
        }

        // 同じ確認済みの記録は追加しない
        $now = CarbonImmutable::now();
        ViewedOnceNotification::query()->insertOrIgnore([
            'notification_id' => $notification->id,
            'student_id' => $student->id,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
