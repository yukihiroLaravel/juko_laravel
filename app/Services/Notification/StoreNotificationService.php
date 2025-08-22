<?php

namespace App\Services\Notification;

use App\Model\Notification;

class StoreNotificationService
{
    
    /**
     * 通知を保存する共通処理
     */
    public function __invoke(
        int $course_id,
        int $instructor_id,
        string $title,
        string $type,
        string $start_date,
        string $end_date,
        string $content,
        string $status
    ): void {
        
        Notification::create([
            'course_id' => $course_id,
            'instructor_id' => $instructor_id,
            'title' => $title,
            'type' => $type,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'status' => $status,
            'content' => $content,
        ]);
    }
}
