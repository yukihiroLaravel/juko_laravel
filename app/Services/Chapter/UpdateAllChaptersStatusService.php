<?php

namespace App\Services\Chapter;

use App\Enums\Chapter\StatusEnum;
use App\Model\Chapter;
use App\Services\Chapter\StatusTransitionService;

class UpdateAllChaptersStatusService
{
    /**
     * 対象の講義の全チャプターのステータスを一括更新
     *
     * @param  'public'|'private'  $status
     */
    public function __invoke(int $courseId, string $status): void
    {
        $chapters = Chapter::where('course_id', $courseId)->get();

        $statusTransitionService = new StatusTransitionService;
        $statusTransitionService($chapters, StatusEnum::from($status));
        
        Chapter::where('course_id', $courseId)
            ->whereIn('status', StatusEnum::switchableStatuses())
            ->update(['status' => $status]);
    }
}
