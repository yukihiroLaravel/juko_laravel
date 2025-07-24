<?php

namespace App\Services\Notification;

use App\Model\Notification;
use Illuminate\Support\Collection;

class BulkUpdateNotificationStatusService
{
    /**
     * お知らせのステータスを一括更新する
     */
    public function __invoke(Collection $notifications, string $status): void
    {
        Notification::whereIn('id', $notifications->pluck('id'))->update(['status' => $status]);
    }
}