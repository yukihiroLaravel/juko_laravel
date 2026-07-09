<?php

namespace App\Services\Lesson;

use App\Enums\Lesson\StatusEnum;
use App\Model\Lesson;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BulkUpdateLessonStatusService
{
    // 判定器をコンストラクタで受け取る（DI）
    public function __construct(private readonly StatusTransitionService $statusTransitionService) {}

    /**
     * レッスンのステータスを一括更新する
     *
     * @param  Collection<int, Lesson>  $lessons
     * @param  string  $status  'public'|'private'
     */
    public function __invoke(Collection $lessons, string $status): void
    {
        // 1. 新しいステータスをEnumに変換する
        $newStatus = StatusEnum::tryFrom($status);

        // 2. 「下書き(draft)」状態のレッスンを一括更新の対象から除外（スルー）する
        $targetLessons = $lessons->filter(fn (Lesson $lesson) => $lesson->status !== StatusEnum::DRAFT);

        // 3. 対象が1件もない場合は何もせずに終了
        if ($targetLessons->isEmpty()) {
            return;
        }

        // 4. 一連のチェックと更新を単一のトランザクション（一蓮托生）で行う
        DB::transaction(function () use ($targetLessons, $newStatus, $status) {

            // ① 除外した後に残ったレッスン1件ずつに対して、遷移チェックをかける
            foreach ($targetLessons as $lesson) {
                $this->statusTransitionService->validateTransition($lesson->status, $newStatus);
            }

            // ② 誰ひとりエラーにならずに全員通過したら、対象レッスンをまとめて一括更新！
            $targetIds = $targetLessons->pluck('id')->toArray();
            Lesson::whereIn('id', $targetIds)->update([
                'status' => $status,
            ]);
        });
    }
}
