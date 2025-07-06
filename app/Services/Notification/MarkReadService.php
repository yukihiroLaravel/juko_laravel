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
    public function __invoke(Student $student, int $notificationId): void
    {
        // typeがONCEのものだけを取得
        $OnceNotification = Notification::where('id', $notificationId)
            ->where('type', TypeEnum::ONCE)
            ->with('students')
            ->first();
        // 存在しない場合、処理中断
        if (!$OnceNotification) {
            return;
        }

        // ユーザが確認したお知らせを登録(既読登録)
        if (!$OnceNotification->students->contains($student->id)) {
            $OnceNotification->students()->attach($student->id);
        }
    }
}
