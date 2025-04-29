<?php

namespace App\Services\Chapter;

use App\Model\Chapter;
use App\Model\Lesson;
use App\Model\LessonAttendance;
use Illuminate\Auth\Access\AuthorizationException;

class BulkDeleteChapterService
{
    /**
     * @param array $chapterIds
     * @return void
     * @throws AuthorizationException
     */
    public function __invoke(array $chapterIds): void
    {
        $chapters = Chapter::with('lessons')->whereIn('id', $chapterIds)->get();
        $lessonIds = $chapters->pluck('lessons.*.id')->flatten();
        if (LessonAttendance::whereIn('lesson_id, $lessonIds')->exists()){
            throw new AuthorizationException('Forbidden, this lesson has attendance.');
        }
        // チャプターに紐づくレッスンを削除
        Lesson::whereIn('chapter_id', $chapterIds)->delete();

        // チャプター削除
        Chapter::whereIn('id', $chapterIds)->delete();
    }
}
