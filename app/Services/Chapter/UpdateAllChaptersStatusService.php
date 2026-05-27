<?php

namespace App\Services\Chapter;

use App\Enums\Chapter\StatusEnum;
use App\Model\Chapter;

class UpdateAllChaptersStatusService
{
    /**
     * 対象の講義の全チャプターのステータスを一括更新
     *
     * @param  'public'|'private'  $status
     */
    public function __invoke(int $courseId, string $status, StatusTransitionService $service): void
    {
        $chapters = Chapter::where('course_id', $courseId)
            ->whereIn('status', StatusEnum::switchableStatuses())
            ->get();

        $targetStatus = StatusEnum::from($status);
        $chapters->each(fn (Chapter $c) => $service($c->status, $targetStatus)); 
        
        Chapter::where('course_id', $courseId)
            ->whereIn('status', StatusEnum::switchableStatuses())
            ->update(['status' => $targetStatus->value]);
    }
}
