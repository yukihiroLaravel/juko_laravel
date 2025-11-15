<?php

namespace App\Services\Notification;

use App\Dto\Student\Notification\IndexDto;
use App\Enums\Notification\StatusEnum;
use App\Model\Attendance;
use App\Model\Notification;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\LengthAwarePaginator;

class IndexService
{
    /**
     * お知らせ取得
     */
    public function __invoke(IndexDto $dto): LengthAwarePaginator
    {
        $studentId = $dto->studentId;
        $now = CarbonImmutable::now();

        // ① 受講生の受講情報をまとめて取得
        $attendances = Attendance::where('student_id', $studentId)->get();

        // 受講している講座がなければ空のページネーションを返す
        if ($attendances->isEmpty()) {
            return new LengthAwarePaginator(collect(), 0, $dto->perPage, $dto->page);
        }

        // ② course_id => Attendance のマップを作る
        $attendanceByCourse = $attendances->keyBy('course_id');

        // ③ Notification 用の course_id 一覧
        $courseIds = $attendanceByCourse->keys();

        // ④ 基本条件だけ DB で絞る
        $items = Notification::with([
                'students',
                'course:id,title', 
            ])
            ->whereIn('course_id', $courseIds)
            ->where('status', StatusEnum::PUBLIC)
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->orderBy($dto->sortBy, $dto->order)
            ->get();

            // 受講期限が null（期限なし） or 未来（now以降）だけ残す
        $filtered = $items->filter(function (Notification $n) use ($attendanceByCourse, $now) {
            $att = $attendanceByCourse->get($n->course_id);
            if (!$att || is_null($att->attendance_deadline)) return true;
            return $att->attendance_deadline->toDateString() >= $now->toDateString(); 
        })->values();

        // 各通知へ受講情報を紐付け
        $filtered->each(function (Notification $n) use ($attendanceByCourse) {
            if ($att = $attendanceByCourse->get($n->course_id)) {
                $n->setRelation('student_attendance', $att);
            }
        });

        // ⑦ フィルタ済みコレクションを手動ページネーション
        $total   = $filtered->count();
        $page    = max(1, (int) $dto->page);
        $perPage = max(1, (int) $dto->perPage);

        $slice = $filtered->forPage($page, $perPage)->values();

        return new LengthAwarePaginator($slice, $total, $perPage, $page);
    }
}