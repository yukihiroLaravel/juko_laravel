<?php

namespace App\Services\Notification;

use App\Model\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;

class UpdateTypeService
{
    /**
     * 認可チェックと対象の通知を返す
     */
    public function authorizeAndGetNotifications(array $notificationIds): Collection 
    {
        $authGuard = 'instructor';       // 固定化
        $ownerColumn = 'instructor_id';  // 固定化

        $user = Auth::guard($authGuard)->user();
        $notifications = Notification::whereIn('id', $notificationIds)->get();

        if (!Gate::forUser($user)->allows('update', [$notifications, $ownerColumn])) {
            throw new AuthorizationException('You are not authorized to update these notifications.');
        }

        return $notifications;
    }

        /**
     * 通知タイプの更新処理（保存処理）
     */
    public function updateNotificationType(Collection $notifications, string $newType): void
    {
        $notifications->each(function ($notification) use ($newType) {
            $notification->type = $newType;
            $notification->save();
        });
    }
}