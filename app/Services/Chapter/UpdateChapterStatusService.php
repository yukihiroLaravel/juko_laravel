<?php

namespace App\Services\Chapter;

use App\Enums\Chapter\StatusEnum;
use App\Model\Chapter;
use Illuminate\Support\Collection;

class UpdateChapterStatusService
{
    public function __construct(
        private readonly StatusTransitionService $service,
    ) {}

    /**
     * @param  Collection<int>  $chapterIds
     * @param  'private'|'public'  $status
     */
    public function __invoke(Collection $chapterIds, string $status): void
    {
        $targetStatus = StatusEnum::from($status);
        $chapters = Chapter::whereIn('id', $chapterIds)->get();

        // 全チャプターの遷移可否を検証する（1件でも不可なら例外で中断し、更新は行わない）
        $chapters->each(fn (Chapter $chapter) => ($this->service)($chapter->status, $targetStatus));

        Chapter::whereIn('id', $chapters->modelKeys())->update([
            'status' => $targetStatus->value,
        ]);
    }
}
