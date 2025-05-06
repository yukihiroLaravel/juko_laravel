<?php

namespace App\Services\Chapter;

use App\Model\Chapter;

class SortLessonsService
{
    public function __invoke(int $courseId, array $chapters): void
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
