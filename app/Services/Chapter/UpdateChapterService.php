<?php

namespace App\Services\Chapter;

use App\Model\Chapter;

class UpdateChapterService
{
    /**
     * チャプターのタイトルを更新する
     */
    public function __invoke(int $chapterId, string $newTitle): void
    {
        $chapter = Chapter::findOrFail($chapterId);

        $chapter->update([
            'title' => $newTitle,
        ]);
    }
}
