<?php

namespace App\Services\Chapter;

use App\Model\Chapter;
use App\Model\Lesson;

class BulkDeleteChapterService
{
    /**
     * @param array $chapterIds
     * @return void
     */
    public function __invoke(array $chapterIds): void
    {
        // チャプターに紐づくレッスンを削除
        Lesson::whereIn('chapter_id', $chapterIds)->delete();

        // チャプター削除
        Chapter::whereIn('id', $chapterIds)->delete();
    }
}
