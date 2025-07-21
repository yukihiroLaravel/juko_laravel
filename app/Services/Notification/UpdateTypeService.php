<?php

namespace App\Services\Notification;

use App\Model\Notification;
use Illuminate\Support\Collection;

class UpdateTypeService
{
    /**
     * 通知タイプを一括更新
     *
     * @param  Collection<int, Notification>  $notifications
     */
    public function __invoke(Collection $notifications, string $type): void
    {
        Notification::whereIn('id', $notifications->pluck('id'))
            ->update(['type' => $type]);
    }
}
