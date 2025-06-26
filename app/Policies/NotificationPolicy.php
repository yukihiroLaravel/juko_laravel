<?php

namespace App\Policies;

use App\Model\Instructor;
use App\Model\Notification;

class NotificationPolicy
{
    /**
     * お知らせの更新処理に関する認可処理
     */
    public function update(Instructor $instructor, Notification $notification)
    {
        // マネージャーの場合は配下の講師レッスンも更新可能
        if ($instructor->isManager()) {
            $instructorIds = $instructor->managings->pluck('id')->toArray();
            $instructorIds[] = $instructor->id;

            return in_array($notification->instructor_id, $instructorIds, true);
        }

        // マネージャー権限のない講師の場合は自分のレッスンのみ更新可能
        return $notification->instructor_id === $instructor->id;
    }

    /**
     * お知らせの削除に関する認可処理
     */
    public function delete(Instructor $instructor, Notification $notification): bool
    {
        // マネージャーの場合は配下の講師レッスンも削除可能
        if ($instructor->isManager()) {
            $instructorIds = $instructor->managings->pluck('id')->toArray();
            $instructorIds[] = $instructor->id;

            return in_array($notification->instructor_id, $instructorIds, true);
        }

        // マネージャー権限のない講師の場合は自分のレッスンのみ削除可能
        return $notification->instructor_id === $instructor->id;
    }
}
