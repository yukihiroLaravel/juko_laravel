<?php

namespace App\Services\Notification;

use App\Model\Notification;
use Illuminate\Support\Collection;
use Illuminate\Auth\Access\AuthorizationException;

class UpdateTypeService
{
    /**
     * 通知タイプを一括更新
     *
     * @param  Collection<int, Notification>  $notifications
     * @param  array<int>  $allowedInstructorIds
     * @param  string  $type
     * @throws AuthorizationException
     */
    public function __invoke(Collection $notifications, array $allowedInstructorIds, string $type): void
    {
        // 認可チェック
        if (
            $notifications->contains(
                fn (Notification $notification) => !in_array($notification->instructor_id, $allowedInstructorIds, true)
            )
        ) {
            throw new AuthorizationException('Invalid instructor_id.');
        }

        // タイプ一括更新
        Notification::whereIn('id', $notifications->pluck('id'))->update(['type' => $type]);
    }
}
