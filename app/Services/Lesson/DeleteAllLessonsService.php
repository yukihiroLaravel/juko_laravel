<?php

namespace App\Services\Lesson;

use App\Model\Lesson;
use App\Model\LessonAttendance;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;

class DeleteAllLessonsService
{
    /**
     * @param  Collection<int, Lesson>  $lessons
     */
    public function __invoke(Collection $lessons): void
    {
        $lessonIds = $lessons->pluck('id');

        // 出席済みのレッスンがあれば削除不可
        $attendedLessonIds = LessonAttendance::whereIn('lesson_id', $lessonIds)->pluck('lesson_id');
        if ($attendedLessonIds->isNotEmpty()) {
            throw new AuthorizationException('Forbidden, this lesson has attendance.');
        }

        // 削除対象レッスンのorderカラムを0に設定する
        Lesson::whereIn('id', $lessonIds)->update(['order' => 0]);

        // レッスン削除
        Lesson::whereIn('id', $lessonIds)->delete();
    }
}
