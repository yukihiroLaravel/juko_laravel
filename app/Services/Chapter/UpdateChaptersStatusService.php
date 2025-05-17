<?php

namespace App\Services\Chapter;

use App\Models\Chapter;
use Illuminate\Support\Collection;

class UpdateChapterStatusService
{
    /**
     * @param  Collection<int>  $chapterIds
     */
    public function __invoke(Collection $chapterIds, int $status): void
    {
        Chapter::whereIn('id', $chapterIds)->update([
            'status' => $status,
        ]);
    }
}
