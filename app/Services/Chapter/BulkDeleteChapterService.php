<?php

namespace App\Services\Chapter;

use App\Model\Chapter;
use App\Model\Lesson;
use App\Model\LessonAttendance;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;

class BulkDeleteChapterService
{
    /**
     * @param  array<int>  $chapterIds
     * @param  Collection<int, Chapter>  $chapters
     *
     * @throws AuthorizationException
     */
    public function __invoke(array $chapterIds, Collection $chapters): void
    {
        $lessonIds = $chapters->pluck('lessons.*.id')->flatten();
        if (LessonAttendance::whereIn('lesson_id', $lessonIds)->exists()) {
            throw new AuthorizationException('Forbidden, this chapter has lessons with attendance.');
        }
        // チャプターに紐づくレッスンを削除
        Lesson::whereIn('chapter_id', $chapterIds)->delete();

        // チャプター削除
        Chapter::whereIn('id', $chapterIds)->delete();
    }
}
