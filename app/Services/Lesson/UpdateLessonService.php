<?php

namespace App\Services\Lesson;

use App\Model\Lesson;

class UpdateLessonService
{
    public function __invoke(Lesson $lesson, array $data): void
    {
        /**
         * レッスン内容を更新する
         *
         * @param Lesson $lesson
         * @param array{
         *     title: string,
         *     url: string,
         *     remarks: string|null,
         *     status: int
         * } $data 更新するデータ
         * @return void
         */
        $lesson->update([
            'title' => $data['title'],
            'url' => $data['url'],
            'remarks' => $data['remarks'],
            'status' => $data['status'],
        ]);
    }
}
