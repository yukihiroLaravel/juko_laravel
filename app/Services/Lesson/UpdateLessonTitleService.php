<?php

namespace App\Services\Lesson;

use App\Model\Lesson;

final class UpdateLessonTitleService
{
    /**
     * レッスンタイトルを更新する
     *
     * @param int
     * @param string
     * @return bool
     */
    public function __invoke(int $lessonId, string $title): bool
    {
        $lesson = Lesson::findOrFail($lessonId);
        $lesson->title = $title;
        return $lesson->save();
    }
}