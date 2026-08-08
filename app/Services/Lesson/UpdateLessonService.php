<?php

namespace App\Services\Lesson;

use App\Enums\Lesson\StatusEnum;
use App\Model\Lesson;
use App\Services\LessonAttendance\GenerateForPublishedLessonService;

class UpdateLessonService
{
    public function __construct(
        private readonly StatusTransitionService $statusTransitionService,
        private readonly GenerateForPublishedLessonService $generateForPublishedLessonService,
    ) {}

    /**
     * レッスン内容を更新する
     */
    public function __invoke(Lesson $lesson, string $title, string $url, ?string $remarks, StatusEnum $status): void
    {
        $currentStatus = $lesson->status;

        // 状態遷移が許可されているか検証する
        $this->statusTransitionService->validateTransition($currentStatus, $status);

        $lesson->update([
            'title' => $title,
            'url' => $url,
            'remarks' => $remarks,
            'status' => $status->value,
        ]);

        // 下書きから公開に切り替わったタイミングで、既存受講生分の受講状況を生成する
        if ($currentStatus === StatusEnum::DRAFT && $status === StatusEnum::PUBLIC) {
            $this->generateForPublishedLessonService->execute($lesson);
        }
    }
}
