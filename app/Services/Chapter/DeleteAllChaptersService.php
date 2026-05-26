<?php

namespace App\Services\Chapter;

use App\Model\Chapter;
use App\Model\Course;
use App\Model\Lesson;
use App\Model\LessonAttendance;
use Illuminate\Auth\Access\AuthorizationException;

class DeleteAllChaptersService
{
    /**
     * 指定された講座の全チャプターと、それに紐づくレッスン・出席を削除する
     *
     * @throws AuthorizationException
     */
    public function __invoke(int $courseId): void
    {
        $course = Course::with('chapters.lessons')->findOrFail($courseId);

        // すべての lesson ID を取得
        $lessonIds = $course->chapters->pluck('lessons')->flatten()->pluck('id')->toArray();

        if (LessonAttendance::whereIn('lesson_id', $lessonIds)->exists()) {
            throw new AuthorizationException('Forbidden, this chapter has lessons with attendance.');
        }

        // すべての chapter ID を取得
        $chapterIds = $course->chapters->pluck('id')->toArray();

        // レッスンを一括削除
        Lesson::whereIn('id', $lessonIds)->delete();

        // チャプターを一括削除
        Chapter::whereIn('id', $chapterIds)->delete();
    }
}