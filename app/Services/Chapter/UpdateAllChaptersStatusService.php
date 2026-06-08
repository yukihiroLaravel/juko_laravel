<?php

namespace App\Services\Chapter;

use App\Enums\Chapter\StatusEnum;
use App\Model\Chapter;

class UpdateAllChaptersStatusService
{
    public function __construct(
        private readonly StatusTransitionService $service,
    ) {}

    /**
     * 対象の講座の全チャプターのステータスを一括更新
     *
     * @param  'public'|'private'  $status
     */
    public function __invoke(int $courseId, string $status): void
    {
        $targetStatus = StatusEnum::from($status);
        $chapters = Chapter::where('course_id', $courseId)->get();

        // 全チャプターの遷移可否を検証する（1件でも不可なら例外で中断し、更新は行わない）
        $chapters->each(fn (Chapter $chapter) => ($this->service)($chapter->status, $targetStatus));

        Chapter::whereIn('id', $chapters->modelKeys())->update(['status' => $targetStatus->value]);
    }
}
