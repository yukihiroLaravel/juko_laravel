<?php

namespace App\Services\Notification;

use App\Model\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class UpdateTypeService
{
    /**
     * インストラクターまたはマネージャーによる通知更新の認可チェックを共通化
     *
     * @param array $notificationIds
     * @param string $authGuard
     * @param string $ownerColumn
     * @return Collection<Notification>
     * @throws AuthorizationException
     */
    public function __invoke(
        array $notificationIds,
        string $authGuard,
        string $ownerColumn
    ): Collection {
        $user = Auth::guard($authGuard)->user();
        $notifications = Notification::whereIn('id', $notificationIds)->get();

        // if (!Gate::forUser($user)->allows('update', [$notifications, 'instructor_id'])) {
        //     throw new AuthorizationException('You are not authorized to update these notifications.');
        // }

        return $notifications;
    }
}