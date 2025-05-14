<?php

namespace App\Services\Lesson;

use App\Model\Lesson;

class UpdateLessonService
{
    public function __invoke(Lesson $lesson, array $data): void
    {
        /**
         * レッスン内容を更新する
         */

        $lesson->update([
            'title' => $data['title'],
            'url' => $data['url'],
            'remarks' => $data['remarks'],
            'status' => $data['status'],
        ]);
    }
}
