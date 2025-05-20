<?php

namespace App\Services\Chapter;

use App\Model\Course;
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
            foreach ($course->chapters as $chapter) {
                foreach ($chapter->lessons as $lesson) {
                    // 出席データを削除
                    LessonAttendance::where('lesson_id', $lesson->id)->delete();
                }

                // レッスンを削除
                $chapter->lessons()->delete();

                // チャプターを削除
                $chapter->delete();
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
