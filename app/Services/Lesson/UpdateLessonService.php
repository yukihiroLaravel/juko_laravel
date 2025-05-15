<?php

namespace App\Services\Lesson;

use App\Model\Lesson;

class UpdateLessonService
/**
 * レッスン内容を更新する
 *
 * @param  Lesson  $lesson
 * @param array{
 *     title: string,
 *     url: string,
 *     remarks: string|null,
 *     status: string
 * } $data 更新するデータ
 * @return void
 */
{
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
