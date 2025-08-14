<?php

namespace App\Services\Notification;

use App\Dto\Student\Notification\IndexDto;
use App\Enums\Notification\StatusEnum;
use App\Model\Attendance;
use App\Model\Notification;
use Carbon\CarbonImmutable;

class IndexService
{
    /**
     * お知らせ取得
     */
    public function __invoke(IndexDto $dto)
    {
        // ユーザID取得(DTO使用)
        $studentId = $dto->studentId;
        $currentDateTime = CarbonImmutable::now();
        $courseIds = Attendance::where('student_id', $studentId)->pluck('course_id')->toArray();

        // お知らせ取得
        $query = Notification::with('students', 'course')
            ->whereIn('course_id', $courseIds)
            ->where('status', StatusEnum::PUBLIC)
            ->where('start_date', '<=', $currentDateTime)
            ->where('end_date', '>=', $currentDateTime)
            ->whereHas('course', function ($q) use ($currentDateTime) {
                $q->where(function ($sub) use ($currentDateTime) {
                    $sub->whereNull('attendance_deadline') // 期限なし
                        ->orWhere('attendance_deadline', '>=', $currentDateTime);
                });
            });

        // ソート条件とページネーションを適用して結果を返却
        return $query->orderBy($dto->sortBy, $dto->order)
            ->paginate($dto->perPage, ['*'], 'page', $dto->page);
    }
}
