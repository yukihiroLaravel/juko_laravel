<?php

namespace App\Services\Lesson;

use App\Model\Lesson;

class DeleteLessonService
{
    public function __invoke(Lesson $lesson): void
    {
        $lesson->update(['order' => 0]);

        $lesson->delete();

        Lesson::where('chapter_id', $lesson->chapter_id)
            ->orderBy('order')
            ->get()
            ->each(fn (Lesson $lesson, int $index): bool => (bool) $lesson->update(['order' => $index + 1])
            );
    }
}
