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
    public function __invoke(Student $student, array $notifications): void
    {
        // typeがONCEのものだけを取得
        $OnceNotifications = Notification::whereIn('id', $notifications)
            ->where('type', TypeEnum::ONCE)
            ->with('students')
            ->get();

        // ユーザが確認したお知らせを登録(既読登録)
        foreach ($OnceNotifications as $OnceNotification) {
            if (!$OnceNotification->students->contains($student->id)) {
                $OnceNotification->students()->attach($student->id);
            }
        }
    }
}
