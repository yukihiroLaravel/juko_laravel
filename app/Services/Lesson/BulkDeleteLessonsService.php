<?php

namespace App\Services\Lesson;

use App\Model\Lesson;

class BulkDeleteLessonsService
{
    /**
     * 複数レッスンの削除
     *
     * @param  array<int>  $lessonIds
     */
    public function __invoke(array $lessonIds, int $chapterId): void
    {
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
