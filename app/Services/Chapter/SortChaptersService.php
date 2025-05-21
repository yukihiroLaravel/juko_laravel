<?php

namespace App\Services\Chapter;

use App\Model\Chapter;

class SortChaptersService
{
    /**
     * チャプターの並び順を更新する
     *
     * @param  array<int, array{chapter_id: int, order: int}>  $chapters
     */
    public function __invoke(array $chapters, int $courseId): void
    {
        foreach ($chapters as $chapter) {
            Chapter::where('id', $chapter['chapter_id'])
                ->where('course_id', $courseId)
                ->firstOrFail()
                ->update([
                    'order' => $chapter['order'],
                ]);
        }
    }
}
