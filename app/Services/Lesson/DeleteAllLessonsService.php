<?php

namespace App\Services\Lesson;

use App\Model\Chapter;
use App\Model\LessonAttendance;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
