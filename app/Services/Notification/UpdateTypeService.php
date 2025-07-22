<?php

namespace App\Services\Notification;

use App\Model\Notification;
use Illuminate\Support\Collection;

class UpdateTypeService
{
    /**
     * 選択されたお知らせのタイプ変更
     */
    public function __invoke(Collection $notifications, string $type): void
    {
        Notification::whereIn('id', $notifications->pluck('id'))
            ->update(['type' => $type]);
    }
}
