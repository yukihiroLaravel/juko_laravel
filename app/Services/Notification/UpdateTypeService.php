<?php

namespace App\Services\Notification;

use App\Model\Notification;
use Illuminate\Support\Collection;

class UpdateTypeService
{
    /**
     * 通知タイプの更新処理（保存処理）
     */
    public function __invoke(Collection $notifications, string $newType): void
    {
        $notifications->each(function ($notification) use ($newType) {
            $notification->type = $newType;
            $notification->save();
        });
    }
}