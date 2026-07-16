<?php

namespace App\Services\Lesson;

use App\Enums\Lesson\StatusEnum;
use App\Model\Lesson;
use App\Services\LessonAttendance\GenerateForPublishedLessonService;
use Illuminate\Support\Facades\DB;

class UpdateLessonService
{
    // コンストラクタで注入する形式に整理
    public function __construct(
        private StatusTransitionService $transitionService,
        private GenerateForPublishedLessonService $generateForPublishedLessonService
    ) {}

    public function __invoke(Lesson $lesson, string $title, string $url, ?string $remarks, StatusEnum $status): void
    {
        DB::transaction(function () use ($lesson, $title, $url, $remarks, $status) {

            // 1. 【順序変更】更新前のステータスを保持（更新より先に！）
            $oldStatus = $lesson->status;

            // 2. 【バリデーション】ここで更新前の値と新しい値を比較する
            $this->transitionService->validateTransition(
                $lesson->status,
                $status
            );

            // 3. 【DB更新】バリデーションが通ったら、ここで更新する
            $lesson->update([
                'title' => $title,
                'url' => $url,
                'remarks' => $remarks,
                'status' => $status->value,
            ]);

            // 4. 更新後の処理
            if ($oldStatus->value === StatusEnum::DRAFT->value && $status->value === StatusEnum::PUBLIC->value) {
                $this->generateForPublishedLessonService->execute($lesson);
            }
        });
    }
}
