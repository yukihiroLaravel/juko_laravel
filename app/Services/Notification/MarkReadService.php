<?php

namespace App\Services\Notification;

use App\Model\Notification;
use App\Model\Student;
use App\Enums\Notification\TypeEnum;

class MarkReadService
{
    /**
     * 既読登録処理(Type->onceのみ)
     */
    public function __invoke(Student $student, array $notificationIds): void
    {
        // typeがONCEのものだけを取得
        $notifications = Notification::whereIn('id', $notificationIds)
            ->where('type', TypeEnum::ONCE)
            ->with('students')
            ->get();

        // ユーザが確認したお知らせを登録(既読登録)
        foreach ($notifications as $notification) {
            if (!$notification->students->contains($student->id)) {
                $notification->students()->attach($student->id);
            }
        }
    }
}
