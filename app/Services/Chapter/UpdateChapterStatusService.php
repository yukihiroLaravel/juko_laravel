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
    public function __invoke(Collection $chapterIds, string $status): void
    {
        $chapters = Chapter::whereIn('id', $chapterIds)->get();

        $statusTransitionService = new StatusTransitionService;
        $statusTransitionService($chapters, StatusEnum::from($status));

        Chapter::whereIn('id', $chapterIds)->update([
            'status' => $status,
        ]);
    }
}
