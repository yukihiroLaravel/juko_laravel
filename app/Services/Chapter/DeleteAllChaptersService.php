<?php

namespace App\Services\Chapter;

use App\Model\Course;
use App\Model\Lesson;
use App\Model\Chapter;
use App\Model\LessonAttendance;
use Illuminate\Support\Facades\DB;

class DeleteAllChaptersService
{
    /**
     * 指定された講座の全チャプターと、それに紐づくレッスン・出席を削除する
     *
     * @param  int  $courseId
     * @return void
     */
    public function __invoke(int $courseId): void
    {
        $course = Course::with('chapters.lessons')->findOrFail($courseId);

        DB::beginTransaction();

        try {
            // すべての lesson ID を取得
            $lessonIds = $course->chapters->pluck('lessons')->flatten()->pluck('id')->toArray();

            // すべての chapter ID を取得
            $chapterIds = $course->chapters->pluck('id')->toArray();

            // 出席データを一括削除
            LessonAttendance::whereIn('lesson_id', $lessonIds)->delete();

            // レッスンを一括削除
            Lesson::whereIn('id', $lessonIds)->delete();

            // チャプターを一括削除
            Chapter::whereIn('id', $chapterIds)->delete();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
