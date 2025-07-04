<?php

namespace App\Services\Notification;

use App\Model\Notification;
use App\Model\Instructor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Access\AuthorizationException;
use Exception;

class NotificationService
{
    /**
     * 通知のタイプを一括更新（トランザクションあり）
     *
     * @param Collection<Notification> $notifications
     * @param string $type
     * @throws Exception
     */
    public function updateNotificationTypes(Collection $notifications, string $type): void
    {
        DB::beginTransaction();
        try {
            $notifications->each(function (Notification $notification) use ($type) {
                $notification->fill(['type' => $type])->save();
            });

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 認証・所有者チェック＋通知タイプ更新の共通処理
     */
    public function handleUpdateNotificationType(
        array $notificationIds,
        string $notificationType,
        string $authGuard,
        string $ownerColumn
    ): void {
        $userId = Auth::guard($authGuard)->id();
        $notifications = Notification::whereIn('id', $notificationIds)->get();

        if ($notifications->contains(fn ($n) => $n->{$ownerColumn} !== $userId)) {
            throw new AuthorizationException('Invalid ownership.');
        }

        $this->updateNotificationTypes($notifications, $notificationType);
    }

    public function handleManagerUpdateNotificationType(
        array $notificationIds, 
        string $notificationType
        ): void
    {
        $instructorId = Auth::guard('instructor')->id();

        $manager = Instructor::with('managings')->find($instructorId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        $notifications = Notification::whereIn('id', $notificationIds)->get();
        $notificationsInstructorIds = $notifications->pluck('instructor_id')->toArray();

        if (array_diff($notificationsInstructorIds, $instructorIds) !== []) {
            throw new AuthorizationException('Invalid instructor_id.');
        }

        $this->updateNotificationTypes($notifications, $notificationType);
    }
}