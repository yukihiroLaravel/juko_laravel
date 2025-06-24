<?php

namespace App\Services\Notification;

use App\Model\Notification;

class PutNotificationService
{
    /**
     * お知らせの内容を更新
     */
    public function __invoke(
        Notification $notification,
        string $type,
        string $start_date,
        string $end_date,
        string $title,
        string $content,
        string $status
    ): void {
        $notification->fill([
            'type' => $type,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'title' => $title,
            'content' => $content,
            'status' => $status,
        ])
            ->save();
    }
}
