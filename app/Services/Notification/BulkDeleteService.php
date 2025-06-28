<?php

namespace App\Services\Notification;

use Illuminate\Database\Eloquent\Collection;
use App\Model\Notification;
use App\Model\ViewedOnceNotification;

class BulkDeleteService
{
    /**
     * お知らせ内容を削除する
     */
    public function __invoke(Collection $notifications): void
    {
        // 選択されたお知らせidを取得
        $notificationIds = $notifications->pluck('id')->toArray();

        // viewed_once_notificationsテーブルのレコードを一括削除
        ViewedOnceNotification::whereIn('notification_id', $notificationIds)->delete();

        // notificationsテーブルのレコードを一括削除
        Notification::whereIn('id', $notificationIds)->delete();
    }
}
