<?php

namespace App\Services\Notification;

use App\Model\Notification;

class UpdateTypeAllService
{
    /**
     * お知らせのタイプを一括更新するサービス
     *
     * @param  array<int>  $instructorIds
     * @param  'always' | 'once'  $notificationType
     */
    public function updateNotificationType(array $instructorIds, string $notificationType): void
    {
        Notification::whereIn('instructor_id', $instructorIds)->update([
            'type' => $notificationType,
        ]);
    }
}
