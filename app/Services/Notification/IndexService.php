<?php

namespace App\Services\Notification;

use App\Dto\Student\Notification\IndexDto;
use App\Model\Notification;
use Carbon\CarbonImmutable;
use App\Enums\Notification\StatusEnum;
use App\Model\Attendance;

class IndexService
{
    /**
     * お知らせ取得
     */
    public function __invoke(IndexDto $dto)
    {
        // ユーザID取得(DTO使用)
        $studentId = $dto->getStudentId();
        $currentDateTime = CarbonImmutable::now();
        $courseIds = Attendance::where('student_id', $studentId)->pluck('course_id')->toArray();


        // お知らせ取得
        $query = Notification::with('students', 'course')
            ->whereIn('course_id', $courseIds)
            ->where('status', StatusEnum::PUBLIC)
            ->where('start_date', '<=', $currentDateTime)
            ->where('end_date', '>=', $currentDateTime);

        // 既読・未読状態取得(DTO仕様)
        $filter = $dto->getFilter();
        // 既読データ取得
        if($filter === 'read'){
            $query->whereHas('students', function($q) use ($studentId){
                $q->where('student_id', $studentId);
            });
        // 未読データ取得
        }elseif($filter === 'unread'){
            $query->whereDoesntHave('students', function ($q) use ($studentId) {
                $q->where('student_id', $studentId);
            });
        }

        // ソート条件とページネーションを適用して結果を返却
        return $query->orderBy($dto->getSortBy(), $dto->getOrder())
            ->paginate($dto->getPerPage(), ['*'], 'page', $dto->getPage());
    }
}
