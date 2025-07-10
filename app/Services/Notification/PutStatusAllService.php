<?php

namespace App\Services\Notification;

use App\Enums\Notification\StatusEnum;
use App\Model\Notification;
use Illuminate\Database\Eloquent\Collection;

class PutStatusAllService
{
    /**
     * お知らせ内容を削除する
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, \App\Model\Notification>  $notifications
     */
    public function __invoke(StatusEnum $status, Collection $notifications): void
    {
        $notifications->each(function (Notification $notification) use ($status) {
            $notification->fill([
                'status' => $status,
            ])->save();
        });
    }
}
