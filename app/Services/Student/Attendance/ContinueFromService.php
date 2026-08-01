<?php

namespace App\Services\Student\Attendance;

use App\Dto\Student\Attendance\ContinueFromDto;
use App\Model\Attendance;
use App\Model\Lesson;
use App\Model\LessonAttendance;

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
                ->first(fn (Lesson $lesson) => ! $this->isCompletedLesson($attendance, $lesson));

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

    /**
     * 該当レッスンを受講済みかどうかを判定する
     */
    private function isCompletedLesson(Attendance $attendance, Lesson $lesson): bool
    {
        $lessonAttendance = $attendance->lessonAttendances->firstWhere('lesson_id', $lesson->id);

        return $lessonAttendance?->status === LessonAttendance::STATUS_COMPLETED_ATTENDANCE;
    }
}
