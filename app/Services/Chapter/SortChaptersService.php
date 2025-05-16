<?php

namespace App\Services\Chapter;

use App\Model\Chapter;

class SortChaptersService
{
    /**
     * チャプターの並び順を更新する
     *
     * @param  array  $chapters  チャプターIDと並び順を含む配列
     * @param  int  $courseId  講座ID
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
