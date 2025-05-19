<?php

namespace App\Services\Chapter;

use App\Model\Course;

class DeleteAllChaptersService
{
    /**
     * 指定された講座の全チャプターを削除する
     *
     * @param  int  $courseId
     * @return void
     */
    public function __invoke(int $courseId): void
    {
        $course = Course::findOrFail($courseId);
        $course->chapters()->delete();
    }
}
