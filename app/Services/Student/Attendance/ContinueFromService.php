<?php

namespace App\Services\Student\Attendance;

use App\Dto\Student\Attendance\ContinueFromDto;
use App\Model\Attendance;
use App\Model\Lesson;

class ContinueFromService
{
    /**
     * 受講の「続きから」情報を取得する
     * （最初の未完了レッスンとそのチャプターのID・タイトルを返す）
     */
    public function __invoke(Attendance $attendance): ?ContinueFromDto
    {
        $attendance->loadMissing(['course.publicChapters.publicLessons', 'lessonAttendances']);

        foreach ($attendance->course->publicChapters as $chapter) {
            $incompleteLesson = $chapter->publicLessons
                ->first(fn (Lesson $lesson) => ! $attendance->hasCompletedLesson($lesson));

            if ($incompleteLesson instanceof Lesson) {
                return new ContinueFromDto(
                    chapterId: $chapter->id,
                    chapterTitle: $chapter->title,
                    lessonId: $incompleteLesson->id,
                    lessonTitle: $incompleteLesson->title,
                );
            }
        }

        return null;
    }
}
