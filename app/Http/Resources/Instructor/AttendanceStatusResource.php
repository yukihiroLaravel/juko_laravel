<?php

namespace App\Http\Resources\Instructor;

use App\Model\LessonAttendance;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceStatusResource extends JsonResource
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
        $course = $attendance->course;

        return [
            'attendance_id' => $attendance->id,
            'progress' => $attendance->progress,
            'course' => [
                'course_id' => $course->id,
                'title' => $course->title,
                'status' => $course->status,
                'image' => $course->image,
                'chapter' => $this->calculateChapterProgress($course, $attendance),
            ],
        ];
    }

    /**
     * チャプターの進捗計算
     *
     * @param  \App\Models\Course  $course
     * @param  \App\Models\Attendance  $attendance
     * @return array
     */
    private function calculateChapterProgress($course, $attendance)
    {
        return $course->chapters->map(function ($chapter) use ($attendance) {
            $completedCount = $this->calculateCompletedLessonCount($chapter, $attendance);
            $totalLessonsCount = $chapter->lessons->count();
            $chapterProgress = $totalLessonsCount > 0 ? ($completedCount / $totalLessonsCount) * 100 : 0;

            return [
                'chapter_id' => $chapter->id,
                'title' => $chapter->title,
                'status' => $chapter->status,
                'progress' => $chapterProgress,
            ];
        })->toArray();
    }

    /**
     * チャプター内完了済みレッスン数計算
     *
     * @param  \App\Models\Chapter  $chapter
     * @param  \App\Models\Attendance  $attendance
     * @return int
     */
    private function calculateCompletedLessonCount($chapter, $attendance)
    {
        return $chapter->lessons->filter(function ($lesson) use ($attendance) {
            $lessonAttendance = $lesson->lessonAttendances->firstWhere('attendance_id', $attendance->id);
            return $lessonAttendance && $lessonAttendance->status === LessonAttendance::STATUS_COMPLETED_ATTENDANCE;
        })->count();
    }
}