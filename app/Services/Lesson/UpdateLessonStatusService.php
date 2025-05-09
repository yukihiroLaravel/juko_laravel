<?php

namespace App\Services\Lesson;

use App\Model\Lesson;

class UpdateLessonStatusService
{
    /**
     * レッスンのステータスを更新する
     *
     * @param  Lesson  $lesson
     * @param  string  $status
     * @return Lesson
     */
    public function __invoke(Lesson $lesson, string $status): void
    {
        $lesson->update([
            'status' => $status,
        ]);
    }
}
