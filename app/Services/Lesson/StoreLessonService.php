<?php

namespace App\Services\Lesson;

use App\Model\Lesson;

class StoreLessonService
{
    public function __invoke(
        int $chapter_id,
        string $title,
        string $status
    ): Lesson {
        $nextOrder = Lesson::where('chapter_id', $chapter_id)->max('order') + 1;

        return Lesson::create([
            'chapter_id' => $chapter_id,
            'title' => $title,
            'status' => $status,
            'order' => $nextOrder,
        ]);
    }
}
