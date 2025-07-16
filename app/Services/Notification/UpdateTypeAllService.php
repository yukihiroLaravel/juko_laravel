<?php

namespace App\Services\Notification;

use App\Model\Notification;

class UpdateTypeAllService
{
    /**
     * 指定されたinstructorId配列に属する通知タイプを一括更新
     */
    public function updateNotificationType(array $instructorIds, string $notificationType): void
    {
        Notification::whereIn('instructor_id', $instructorIds)->update([
            'type' => $notificationType,
        ]);
    }
}
