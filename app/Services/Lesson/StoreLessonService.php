<?php

namespace App\Services\Lesson;

use App\Enums\Lesson\StatusEnum;
use App\Model\Lesson;

class StoreLessonService
{
    public function __invoke(
        int $chapterId,
        string $title,
    ): Lesson {
        $nextOrder = Lesson::where('chapter_id', $chapterId)->max('order') + 1;

        return Lesson::create([
            'chapter_id' => $chapterId,
            'title' => $title,
            'status' => StatusEnum::DRAFT->value,
            'order' => $nextOrder,
        ]);
    }
}
