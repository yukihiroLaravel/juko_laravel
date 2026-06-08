<?php

namespace App\Services\Notification;

use App\Dto\Student\Notification\IndexDto;
use App\Enums\Course\DeadlineTypeEnum;
use App\Enums\Notification\StatusEnum;
use App\Model\Attendance;
use App\Model\Notification;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

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
        $query = Notification::with('students', 'course', 'instructor')
            ->whereIn('course_id', $courseIds)
            ->where('status', StatusEnum::PUBLIC)
            ->where('start_date', '<=', $currentDateTime)
            ->where('end_date', '>=', $currentDateTime)
            ->whereHas('course', function (Builder $q) use ($studentId, $currentDateTime) {
                $q->where(function (Builder $sub) use ($studentId, $currentDateTime) {
                    $sub
                        // ① 期限なし（none）
                        ->where('deadline_type', DeadlineTypeEnum::NONE->value)
                        // ②・③ 固定期限日（fixed_date）と相対日数（relative_days）
                        ->orWhereHas('attendances', function (Builder $q2) use ($studentId, $currentDateTime) {
                            $q2->where('student_id', $studentId)
                                ->where('attendance_deadline', '>=', $currentDateTime);
                        });
                });
            });

        // ソート条件を適用
        if ($dto->sortBy === Notification::SORT_BY_INSTRUCTOR_NICK_NAME) {
            $query->join('instructors', 'notifications.instructor_id', '=', 'instructors.id')
                ->select('notifications.*')
                ->orderBy('instructors.nick_name', $dto->order)
                ->orderBy('notifications.id', 'asc');
        } else {
            $query->orderBy($dto->sortBy, $dto->order);
        }

        // ページネーションを適用して結果を返却
        return $query->paginate($dto->perPage, ['*'], 'page', $dto->page);
    }
}
