<?php

namespace App\Services\Lesson;

use App\Model\Lesson;

class UpdateLessonStatusService
{
    /**
     * レッスンのステータスを更新する
     */
    public function __invoke(Lesson $lesson, string $status): void
    {
        $lesson->update([
            'status' => $status,
        ]);
    }
}
