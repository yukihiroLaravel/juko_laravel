<?php

namespace App\Services\Notification;

use App\Enums\Notification\TypeEnum;
use App\Model\Notification;
use App\Model\Student;
use Illuminate\Auth\Access\AuthorizationException;
use Carbon\CarbonImmutable;

class MarkReadService
{
    /**
     * お知らせ既読処理
     */
    public function __invoke(Student $student, int $notificationId): void
    {
        $notification = Notification::where('id', $notificationId)
            ->where('type', TypeEnum::ONCE)
            ->with(['students', 'course'])
            ->firstOrFail();

        if (
            $notification->course->attendance_deadline &&
            CarbonImmutable::now()->gte(
                $notification->course->attendance_deadline->endOfDay()
            )
        ) {
            throw new AuthorizationException('The course has expired.');
        }       

        // ユーザが確認したお知らせを登録(既読登録)
        if (! $notification->students->contains($student->id)) {
            $notification->students()->attach($student->id);
        }
    }
}
