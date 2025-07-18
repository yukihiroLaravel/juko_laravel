<?php

namespace App\Services\Lesson;

use App\Model\Lesson;

class UpdateLessonTitleService
{
    /**
     * レッスンタイトルを更新する
     */
    public function __invoke(Lesson $lesson, string $title): void
    {
        $lesson->title = $title;
        $lesson->save();
    }
}