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
     * お知らせの一括更新処理に関する認可処理
     *
     * @param  Collection<int, Notification>  $notifications
     */
    public function bulkUpdate(Instructor $instructor, Collection $notifications): bool
    {
        if ($instructor->isManager()) {
            // 管理者の場合、配下の講師の講座も更新可能
            $managerIds = $instructor->managings->pluck('id')->toArray();
            $managerIds[] = $instructor->id;

            return $notifications->every(
                fn (Notification $notification) => in_array($notification->instructor_id, $managerIds, true)
            );
        }

        // 講師の場合、自分の講座のみ更新可能
        return $notifications->every(
            fn (Notification $notification) => $notification->instructor_id === $instructor->id
        );
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

    /**
     * お知らせの新規作成に関する認可処理
     *
     * マネージャーのみ作成可能。講師は作成できない。
     *
     * @param  \App\Model\Instructor  $instructor  ログイン中のユーザー
     * @param  \App\Model\Course  $course  お知らせを作成する対象コース
     * @return bool
     */
    public function create(Instructor $instructor, \App\Model\Course $course): bool
    {
        // マネージャーのみ作成可能
        if ($instructor->isManager()) {
            $instructorIds = $instructor->managings->pluck('id')->toArray();
            $instructorIds[] = $instructor->id;

            return in_array($course->instructor_id, $instructorIds, true);
        }

        // 講師は作成できない
        return false;
    }
}
