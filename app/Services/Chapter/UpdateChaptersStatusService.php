<?php

namespace App\Services\Chapter;

use App\Model\Lesson;

class UpdateLessonService{
    public function __invoke(Lesson $lesson): void
    {
        /**
     * @param  Collection<int>  $chapterIds
     * @param  int  $status
     * @return void
     */
    public function __invoke(Collection $chapterIds, int $status): void
    {
        Chapter::whereIn('id', $chapterIds)->update([
            'status' => $status,
        ]);
    }
    }
}