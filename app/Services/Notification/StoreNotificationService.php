<?php

namespace App\Services\Notification;

use App\Model\Notification;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StoreNotificationService
{
    /**
     * 通知を保存する共通処理
     */
    public function __invoke(array $data): void
    {
        DB::beginTransaction();
        try {
            Notification::create([
                'course_id' => $data['course_id'],
                'instructor_id' => $data['instructor_id'],
                'title' => $data['title'],
                'type' => $data['type'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'status' => $data['status'] ?? 'public',
                'content' => $data['content'],
            ]);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            throw $e;
        }
    }
}
