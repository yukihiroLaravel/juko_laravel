<?php

namespace App\Services\Notification;

use App\Enums\Notification\TypeEnum;
use App\Model\Notification;
use App\Model\Student;

class MarkReadService
{
    /**
     * お知らせ既読処理
     */
    public function __invoke(Student $student, int $notificationId): void
    {
        $notification = Notification::where('id', $notificationId)
            ->where('type', TypeEnum::ONCE)
            ->with(['students'])
            ->firstOrFail();

        // ユーザが確認したお知らせを登録(既読登録)
        if (! $notification->students->contains($student->id)) {
            $notification->students()->attach($student->id);
        }
    }
}
