<?php

namespace App\Services\Lesson;

use App\Model\Chapter;
use App\Model\LessonAttendance;
use Illuminate\Auth\Access\AuthorizationException;

class DeleteAllLessonsService
{
    public function __invoke(Chapter $chapter): void
    {
        $lessonIds = $chapter->lessons->pluck('id');

        // 出席済みのレッスンがあれば削除不可
        $attendedLessonIds = LessonAttendance::whereIn('lesson_id', $lessonIds)->pluck('lesson_id');
        if ($attendedLessonIds->isNotEmpty()) {
            throw new AuthorizationException('This lessons contains attendance.');
        }

        // レッスン削除
        $chapter->lessons()->delete();
    }
}
