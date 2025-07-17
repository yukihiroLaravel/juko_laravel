<?php

namespace App\Services\Lesson;

use App\Model\Lesson;

class UpdateLessonTitleService
{
    /**
     * レッスンタイトルを更新する
     */
    public function __invoke(int $lessonId, string $title): bool
    {
        $lesson = Lesson::findOrFail($lessonId);
        $lesson->title = $title;

        return $lesson->save();
    }
}
