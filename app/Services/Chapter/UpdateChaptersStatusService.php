<?php

namespace App\Services\Chapter;

use Illuminate\Support\Collection;
use App\Models\Chapter;

class UpdateChapterStatusService
{
    /**
     * @param  Collection<int>  $chapterIds
     * @param  int  $status
     * @return void
     */
    public function __invoke(Collection $chapterIds, int $status): void
    {
        Chapter::whereIn('id', $chapterIds)->update([
            'status' => $status,
        ]);
    }
}
