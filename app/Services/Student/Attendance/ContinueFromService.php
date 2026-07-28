<?php

namespace App\Services\Student\Attendance;

use App\Dto\Student\Attendance\ContinueFromDto;
use App\Model\Attendance;
use App\Model\Lesson;
use App\Model\LessonAttendance;
use App\Enums\Chapter\StatusEnum as ChapterStatusEnum;
use App\Enums\Lesson\StatusEnum as LessonStatusEnum;

class ContinueFromService
{
    /**
     * 受講の「続きから」情報を取得する
     * （最初の未完了レッスンとそのチャプターのID・タイトルを返す）
     */
    public function __invoke(Attendance $attendance): ?ContinueFromDto
    {
        foreach ($attendance->course->chapters as $chapter) {
            if ($chapter->status !== ChapterStatusEnum::PUBLIC->value) {
                continue;
            }

            $publicLessons = $chapter->lessons->filter(
                fn (Lesson $lesson) => $lesson->status === LessonStatusEnum::PUBLIC->value
            );

            $incompleteLesson = $publicLessons->first(function ($lesson) use ($attendance) {
                $status = $attendance->lessonAttendances
                    ->where('lesson_id', $lesson->id)
                    ->first()?->status;

                return $status !== LessonAttendance::STATUS_COMPLETED_ATTENDANCE;
            });

            if ($incompleteLesson) {
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
