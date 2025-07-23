<?php

namespace App\Services\Notification;

use App\Model\Notification;
use Illuminate\Database\Eloquent\Collection;

class UpdateTypeService
{
    /**
     * 選択されたお知らせのタイプ変更
     *
     * @param  Collection<int, Notification>  $notifications
     * @param  'always'|'once'  $type
     */
    public function __invoke(Collection $notifications, string $type): void
    {
        Notification::whereIn('id', $notifications->pluck('id'))
            ->update(['type' => $type]);
    }
}
