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
        // お知らせ取得クエリ
        $query = Notification::with([
            'students',
            'course.courseDeadline',
            'course.attendances' => fn ($q) => $q
                ->where('student_id', $studentId)
                ->select('id', 'course_id', 'student_id', 'created_at', 'attendance_deadline'),
        ])
            ->whereIn('course_id', $courseIds)
            ->where('status', StatusEnum::PUBLIC)
            ->where('start_date', '<=', $currentDateTime)
            ->where('end_date', '>=', $currentDateTime)
            ->whereHas('course', function (Builder $q) use ($studentId, $currentDateTime) {
                $q->where(function (Builder $sub) use ($studentId, $currentDateTime) {
                    $sub
                        // ① 期限なし（none）
                        ->where('deadline_type', DeadlineTypeEnum::NONE->value)

                        // ② 固定期限（fixed_date）を許可：course_deadlines.fixed_date >= 今日
                        ->orWhere(function (Builder $fx) use ($currentDateTime) {
                            $fx->where('deadline_type', DeadlineTypeEnum::FIXED_DATE->value)
                                ->whereHas('courseDeadline', function (Builder $cd) use ($currentDateTime) {
                                    // fixed_date が DATE 型なら toDateString() 比較が安全
                                    $cd->whereNotNull('fixed_date')
                                        ->where('fixed_date', '>=', $currentDateTime->toDateString());
                                });
                        })

                        // ③ 相対日数（relative_days）：受講生ごとの attendance_deadline >= now
                        ->orWhereHas('attendances', function (Builder $q2) use ($studentId, $currentDateTime) {
                            $q2->where('student_id', $studentId)
                                ->where('attendance_deadline', '>=', $currentDateTime);
                        });
                });
            });

        // ソート条件とページネーションを適用して結果を返却
        return $query->orderBy($dto->sortBy, $dto->order)
            ->paginate($dto->perPage, ['*'], 'page', $dto->page);
    }
}
