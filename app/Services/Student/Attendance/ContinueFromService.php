<?php

namespace App\Services\Student\Attendance;

use App\Model\Attendance;
use App\Model\LessonAttendance;

class ContinueFromService
{
    /**
     * 受講の「続きから」情報を取得する
     * （最初の未完了レッスンとそのチャプターのID・タイトルを返す）
     */
    public function __invoke(Attendance $attendance): ?array
    {
        foreach ($attendance->course->chapters as $chapter) {
            $incompleteLesson = $chapter->lessons->first(function ($lesson) use ($attendance) {
                $status = $attendance->lessonAttendances
                    ->where('lesson_id', $lesson->id)
                    ->first()?->status;

                return $status !== LessonAttendance::STATUS_COMPLETED_ATTENDANCE;
            });

            if ($incompleteLesson) {
                return [
                    'chapter_id'    => $chapter->id,
                    'chapter_title' => $chapter->title,
                    'lesson_id'     => $incompleteLesson->id,
                    'lesson_title'  => $incompleteLesson->title,
                ];
            }
        }

        return null;
    }
}