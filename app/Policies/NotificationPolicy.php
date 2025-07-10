<?php

namespace App\Policies;

use App\Model\Instructor;
use App\Model\Notification;
use Illuminate\Database\Eloquent\Collection;

class NotificationPolicy
{
    /**
     * お知らせの更新処理に関する認可処理
     */
    public function update(Instructor $instructor, Collection $notifications, string $ownerColumn): bool
    {
        // マネージャーの場合（自分と管轄インストラクターの通知を更新可能）
        if ($instructor->isManager()) {
            // 自身 + 管轄インストラクターのIDを取得
            $managerIds = $instructor->managings->pluck('id')->toArray();
            $managerIds[] = $instructor->id;

        // 通知内の全 instructor_id が 管轄リスト内にあるか確認
        $notificationInstructorIds = $notifications->pluck('instructor_id')->unique()->toArray();
        if (array_diff($notificationInstructorIds, $managerIds)) {
            return false;
        }

        return true;
    }

        // 一般インストラクターの場合（自分が所有している通知のみ）
        $userId = $instructor->id;
        return !$notifications->contains(fn($notification) => $notification->{$ownerColumn} !== $userId);

        return true;
    }

    /**
     * お知らせの削除に関する認可処理
     */
    public function delete(Instructor $instructor, Notification $notification): bool
    {
        // マネージャーの場合は配下の講師のお知らせも削除可能
        if ($instructor->isManager()) {
            $instructorIds = $instructor->managings->pluck('id')->toArray();
            $instructorIds[] = $instructor->id;

            return in_array($notification->instructor_id, $instructorIds, true);
        }

        // マネージャー権限のない講師の場合は自分のお知らせのみ削除可能
        return $instructor->id === $notification->instructor_id;
    }

    /**
     * お知らせの一括削除処理に関する認可処理
     *
     * @param  Collection<int, Notification>  $notifications
     */
    public function bulkDelete(Instructor $instructor, Collection $notifications): bool
    {
        if ($instructor->isManager()) {
            // 管理者の場合、配下の講師の講座も削除可能
            $managerIds = $instructor->managings->pluck('id')->toArray();
            $managerIds[] = $instructor->id;

            return $notifications->every(
                fn (Notification $notification) => in_array($notification->instructor_id, $managerIds, true)
            );
        }

        // 講師の場合、自分の講座のみ削除可能
        return $notifications->every(
            fn (Notification $notification) => $notification->instructor_id === $instructor->id
        );
    }
}
