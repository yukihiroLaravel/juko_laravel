<?php

namespace App\Services\Chapter;

use App\Enums\Chapter\StatusEnum as ChapterStatusEnum;
use App\Model\Chapter;
use App\Model\Course;

class CreateChapterService
{
    /**
     * チャプター新規作成サービス
     */
    public function __invoke(Course $course, string $title): Chapter
    {
        $order = $course->chapters->count();
        $newOrder = $order + 1;

        // チャプター新規作成
        return Chapter::create([
            'course_id' => $course->id,
            'title' => $title,
            'order' => $newOrder,
            'status' => ChapterStatusEnum::PUBLIC->value,
        ]);
    }
}
