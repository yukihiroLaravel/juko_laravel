<?php

namespace App\Services\Notification;

use App\Model\Notification;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PutService
{
    /**
     * お知らせ内容を更新する
     */
    public function __invoke(Notification $notification, string $title, string $type, string $start_date, string $end_date, string $status, string $content): void
    {
        try {
            DB::beginTransaction();
            $notification->fill([
                'title' => $title,
                'type' => $type,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'status' => $status,
                'content' => $content,
            ])
                ->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            throw $e;
        }
    }
}
