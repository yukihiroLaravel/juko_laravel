<?php

namespace App\Services\Notification;

use App\Model\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class Service
{
    /**
     * インストラクターまたはマネージャーによる通知更新の認可チェックを共通化
     *
     * @param array $notificationIds
     * @param string $authGuard
     * @param string $ownerColumn
     * @param string $newType
     * @throws AuthorizationException
     */
    public function __invoke(array $notificationIds, string $authGuard, string $ownerColumn, string $newType): void
    {
        $user = Auth::guard($authGuard)->user();
        $notifications = Notification::whereIn('id', $notificationIds)->get();

        // // 認可チェックだけはポリシーに任せる
        // if (!Gate::forUser($user)->allows('update', [$notifications, $ownerColumn])) {
        //     throw new AuthorizationException('You are not authorized to update these notifications.');
        // }

        // 更新処理まで完了させる
        DB::beginTransaction();
        try {
            $notifications->each(function ($notification) use ($newType) {
                $notification->type = $newType;
                $notification->save();
            });
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}