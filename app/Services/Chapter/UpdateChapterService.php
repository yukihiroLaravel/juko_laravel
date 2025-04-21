<?php

namespace App\Services\Chapter;

use App\Model\Chapter;

class UpdateChapterService
{
    /**
     * チャプターのタイトルを更新する
     *
     * @param int $chapterId
     * @param string $newTitle
     * @return void
     */
    public function __invoke(int $chapterId, string $newTitle): void
    {
        $chapter = Chapter::findOrFail($chapterId);

        $chapter->update([
            'title' => $newTitle,
        ]);
    }
}
