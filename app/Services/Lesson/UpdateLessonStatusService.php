<?php

namespace App\Services\Lesson;

use App\Enums\Lesson\StatusEnum;
use App\Model\Lesson;
use App\Services\LessonAttendance\GenerateForPublishedLessonService;
use Illuminate\Support\Facades\DB;

class UpdateLessonStatusService
{
    // 2つの部品をコンストラクタで受け取る（DI）
    public function __construct(private readonly StatusTransitionService $statusTransitionService, private readonly GenerateForPublishedLessonService $generateForPublishedLessonService) {}

    /**
     * レッスンのステータスを更新する
     */
    public function __invoke(Lesson $lesson, string $status): void
    {
        // 1. 文字列（"public" など）から Enum型 に変換する
        $newStatus = StatusEnum::tryFrom($status);

        // 元々の現在のステータスを一時保存しておく
        $oldStatus = $lesson->status;

        // 2. 一連の処理を安全な単一トランザクション内で実行する
        DB::transaction(function () use ($lesson, $newStatus, $oldStatus, $status) {

            // ① ステップ2で作った判定器で、このステータス変更がOKか検証する
            $this->statusTransitionService->validateTransition($oldStatus, $newStatus);

            // ② 検証を通過したら、レッスンの状態を更新する（元の処理）
            $lesson->update([
                'status' => $status,
            ]);

            // ③ もし「下書き(draft) → 公開(public)」への遷移だった場合のみ、受講状況を生成する
            if ($oldStatus === StatusEnum::DRAFT && $newStatus === StatusEnum::PUBLIC) {
                $this->generateForPublishedLessonService->execute($lesson);
            }
        });
    }
}
