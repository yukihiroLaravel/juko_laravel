<?php

namespace App\Services\Notification;

use App\Model\Notification;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PutService
{
    /**
     * お知らせ内容を削除する
     */
    public function __invoke(Notification $notification): void
    {
        // 中間テーブル関係削除
        $notification->students()->detach();
        // お知らせ削除
        $notification->delete();
    }
}
