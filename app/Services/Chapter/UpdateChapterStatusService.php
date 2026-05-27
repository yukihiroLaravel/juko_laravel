<?php

namespace App\Services\Chapter;

use App\Enums\Chapter\StatusEnum;
use App\Model\Chapter;
use Illuminate\Support\Collection;

class UpdateChapterStatusService
{
    /**
     * @param  Collection<int>  $chapterIds
     * @param  'private'|'public'  $status
     */
    public function __invoke(Collection $chapterIds, string $status, StatusTransitionService $service): void
    {
        $chapters = Chapter::whereIn('id', $chapterIds)->get();

        $targetStatus = StatusEnum::from($status);
        $chapters->each(fn (Chapter $c) => $service($c->status, $targetStatus));

        Chapter::whereIn('id', $chapterIds)->update([
            'status' => $targetStatus->value,
        ]);
    }
}
