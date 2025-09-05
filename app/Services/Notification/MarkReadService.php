<?php

namespace App\Services\Notification;

use App\Enums\Notification\TypeEnum;
use App\Model\Notification;
use App\Model\Student;
use App\Model\Attendance;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;

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
        
        // 該当生徒の受講期限(attendance_deadline)を取得
        $deadline = Attendance::where('student_id', $student->id)
            ->where('course_id', $notification->course_id)
            ->value('attendance_deadline');

        if ($deadline !== null && CarbonImmutable::now()->gte(CarbonImmutable::parse($deadline)->endOfDay())) {
            throw new AuthorizationException('The course has expired.');
        }

        // ユーザが確認したお知らせを登録(既読登録)
        if (! $notification->students->contains($student->id)) {
            $notification->students()->attach($student->id);
        }
    }
}
