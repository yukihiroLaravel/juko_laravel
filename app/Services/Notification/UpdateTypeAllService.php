<?php

namespace App\Services\Notification;

use App\Model\Notification;
use Illuminate\Support\Facades\Log;

class UpdateTypeAllService
{
    /**
     * 指定されたinstructorId配列に属する通知タイプを一括更新
     */
    public function updateNotificationType(array $instructorIds, string $notificationType): bool
    {
        try {
            Notification::whereIn('instructor_id', $instructorIds)->update([
                'type' => $notificationType,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Notification update failed: '.$e->getMessage());
            throw $e;
        }
    }
}
