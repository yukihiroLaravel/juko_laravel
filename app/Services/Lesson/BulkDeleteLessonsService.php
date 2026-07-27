<?php

namespace App\Services\Lesson;

use App\Model\Lesson;
use App\Model\LessonAttendance;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class BulkDeleteLessonsService
{
    /**
     * 複数レッスンの削除
     *
     * @param  array<int>  $lessonIds
     * @param int $chapterId
     * @throws AuthorizationException
     */
    public function __invoke(array $lessonIds, int $chapterId): void
    {
        // 指定されたレッスンの中に受講状況が存在するかチェック
        if (LessonAttendance::whereIn('lesson_id', $lessonIds)->exists()) {
            throw new AuthorizationException('Forbidden, this lesson has attendance.');
        }

        // 削除対象レッスンのorderカラムを0に設定する
        Lesson::whereIn('id', $lessonIds)->update(['order' => 0]);
        // 対象レッスンの削除
        Lesson::whereIn('id', $lessonIds)->delete();

        // レッスン順序の更新
        Lesson::where('chapter_id', $chapterId)
            ->orderBy('order')
            ->get()
            ->each(function (Lesson $lesson, int $index) {
                $lesson->update(['order' => $index + 1]);
            });
    }
}
