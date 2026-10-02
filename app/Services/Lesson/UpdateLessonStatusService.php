<?php

namespace App\Services\Lesson;

use App\Enums\Lesson\StatusEnum;
use App\Model\Lesson;
use App\Services\LessonAttendance\GenerateForPublishedLessonService;

class UpdateLessonStatusService
{
    public function __construct(
        private readonly StatusTransitionService $statusTransitionService,
        private readonly GenerateForPublishedLessonService $generateForPublishedLessonService,
    ) {}

    /**
     * レッスンのステータスを更新する
     */
    public function __invoke(Lesson $lesson, StatusEnum $status): void
    {
        $currentStatus = $lesson->status;

        // 状態遷移が許可されているか検証する
        ($this->statusTransitionService)($currentStatus, $status);

        $lesson->update([
            'status' => $status->value,
        ]);

        // 下書きから公開に切り替わったタイミングで、既存受講生分の受講状況を生成する
        if ($currentStatus === StatusEnum::DRAFT && $status === StatusEnum::PUBLIC) {
            ($this->generateForPublishedLessonService)($lesson);
        }
    }
}
