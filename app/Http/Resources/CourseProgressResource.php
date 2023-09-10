<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Model\LessonAttendance;

class CourseProgressResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */    
    public function toArray($request)
    {
        $attendance = $this->resource;
        return [
            'course' =>[
                'course_id' => $attendance->course_id,
                'progress' => $attendance->progress
            ],
            "number_of_completed_chapters" => $this->getCompletedChaptersCount($attendance),
            "number_of_total_chapters" => $this->getTotalChaptersCount($attendance),
            "number_of_completed_lessons" => $this->getCompletedLessonsCount($attendance),
            "number_of_total_lessons" => $this->getTotalLessonsCount($attendance),
            "continue_lesson_id" => $this->getYoungestUnCompletedLessonId($attendance)
        ];        
    }

    /**
     * 完了済みのチャプター数を取得する
     *
     * @param Attendance $attendance
     * @return int
     */
    private function getCompletedChaptersCount($attendance)
    {
        return $attendance->course->chapters->filter(function ($chapter) use ($attendance) {
            $isCompleted = false;
            // 全てのレッスンが完了済みかどうかをチェック
            $chapter->lessons->each(function ($lesson) use ($attendance, &$isCompleted) {
                $lessonAttendance = $attendance->lessonAttendances->where('lesson_id', $lesson->id)->first();
                if ($lessonAttendance->status !== LessonAttendance::STATUS_COMPLETED_ATTENDANCE) {
                    $isCompleted = false;
                    return false;
                }
                $isCompleted = true;
            });
            return $isCompleted;
        })->count();        
    }

    /**
     * チャプター合計を取得する
     *
     * @param Attendance $attendance
     * @return int
     */
    private function getTotalChaptersCount($attendance)
    {
        return $attendance->course->chapters->count();
    }

    /**
     * 完了済みのレッスン数を取得する
     *
     * @param Attendance $attendance
     * @return int
     */
    private function getCompletedLessonsCount($attendance)
    {
        return $attendance->lessonAttendances->filter(function ($lessonAttendance) {
            return $lessonAttendance->status === LessonAttendance::STATUS_COMPLETED_ATTENDANCE;
        })->count();
    }

    /**
     * レッスン合計を取得する
     *
     * @param Attendance $attendance
     * @return int
     */
    private function getTotalLessonsCount($attendance)
    {
        $totalLessonsCount = 0;
        foreach ($attendance->course->chapters as $chapter) {
            $lessonCount = $chapter->lessons->count();
            $totalLessonsCount += $lessonCount; 
        }
        return $totalLessonsCount;
    }

    /**
     * 続きのレッスンIDを取得する
     *
     * @param Attendance $attendance
     * @return int|null
     */
    private function getYoungestUnCompletedLessonId($attendance)
    {
        // IDが最も若い未完了のチャプターの内、IDが最も若い未完了のレッスン
        $youngestUnCompletedLessonId = null;
        $attendance->course->chapters->each(function ($chapter) use ($attendance, &$youngestUnCompletedLessonId) {
            $chapter->lessons->each(function ($lesson) use ($attendance, &$youngestUnCompletedLessonId) {
                $lessonAttendance = $attendance->lessonAttendances->where('lesson_id', $lesson->id)->first();

                if ($lessonAttendance->status !== LessonAttendance::STATUS_COMPLETED_ATTENDANCE) {
                    if ($youngestUnCompletedLessonId === null) {
                        $youngestUnCompletedLessonId = $lesson->id;
                        return;
                    }
                    if ($youngestUnCompletedLessonId > $lesson->id) {
                        $youngestUnCompletedLessonId = $lesson->id;
                    }
                }
            });
        });
        return $youngestUnCompletedLessonId;
    }
}
