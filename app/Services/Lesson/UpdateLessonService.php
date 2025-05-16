<?php

namespace App\Services\Lesson;

use App\Model\Lesson;

class UpdateLessonService
{
    /**
     * レッスン内容を更新する
     */
    public function __invoke(Lesson $lesson, string $title, string $url, ?string $remarks, string $status): void
    {
        $lesson->update([
            'title' => $title,
            'url' => $url,
            'remarks' => $remarks,
            'status' => $status,
        ]);
    }
}
