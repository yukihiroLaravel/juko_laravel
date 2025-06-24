<?php

namespace App\Services\Notification;

use App\Dto\Notification\PutDto;
use App\Model\Notification;

class PutNotificationService
{
    /**
     * お知らせの内容を更新
     */
    public function __invoke(Notification $notification, PutDto $data): void
    {
        $notification->fill([
            'type' => $data->type,
            'start_date' => $data->start_date,
            'end_date' => $data->end_date,
            'title' => $data->title,
            'content' => $data->content,
            'status' => $data->status,
        ])->save();
    }
}
