<?php

namespace App\Services\Notification;

use App\Model\Notification;
use App\Model\Student;
use App\Model\ViewedOnceNotification;
use Carbon\CarbonImmutable;

class MarkReadService
{
    /**
     * お知らせ既読処理
     */
    public function __invoke(Student $student, Notification $notification): void
    {
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
