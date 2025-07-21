<?php

namespace App\Services\Notification;

use App\Model\Notification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UpdateTypeService
{
    /**
     * 通知タイプを一括更新
     *
     * @param  Collection<int, Notification>  $notifications
     *
     * @throws AuthorizationException
     */
    public function __invoke(Collection $notifications, string $type): void
    {  
        Notification::whereIn('id', $notifications->pluck('id'))
            ->update(['type' => $type]);
    }
}
