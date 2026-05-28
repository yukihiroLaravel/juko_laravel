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
     * 対象の講義の全チャプターのステータスを一括更新
     *
     * @param  'public'|'private'  $status
     */
    public function __invoke(int $courseId, string $status): void
    {
        $chapters = Chapter::where('course_id', $courseId)
            ->whereIn('status', StatusEnum::switchableStatuses())
            ->get();

        $targetStatus = StatusEnum::from($status);
        $chapters->each(function (Chapter $c) use ($targetStatus) {
            ($this->service)($c->status, $targetStatus);
        });

        Chapter::where('course_id', $courseId)
            ->whereIn('status', StatusEnum::switchableStatuses())
            ->update(['status' => $targetStatus->value]);
    }
}
