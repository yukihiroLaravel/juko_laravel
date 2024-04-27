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
    $attendance = $this->attendance;

    if ($attendance !== null && is_object($attendance)) {
        return [
            'data' => [
                'attendance_id' => $attendance->id,
                'progress' => $attendance->progress,
                'course' => [
                    'course_id' => $attendance->course->id,
                    'title' => $attendance->course->title,
                    'status' => $attendance->course->status,
                    'image' => $attendance->course->image,
                    'chapters' => $attendance->course->chapters->map(function ($chapter) use ($attendance) {
                        return [
                            'chapter_id' => $chapter->id,
                            'title' => $chapter->title,
                            'status' => $chapter->status,
                            'progress' => $this->calculateChapterProgress($chapter, $attendance),
                            'completed_lessons_count' => $this->calculateCompletedLessonCount($chapter, $attendance),
                        ];
                    }),
                ],
            ],
        ];
    } else {
        return [];
    }
    }

    /**
     * チャプターの進捗計算
     *
     * @param Chapter $chapter
     * @param Attendance $attendance
     * @return float
     */
    private function calculateChapterProgress($chapter, $attendance)
    {
        $completedCount = $this->calculateCompletedLessonCount($chapter, $attendance);
        $totalLessonsCount = $chapter->lessons->count();
        return $totalLessonsCount > 0 ? ($completedCount / $totalLessonsCount) * 100 : 0;
    }

    /**
     * チャプター内完了済みレッスン数計算
     *
     * @param Chapter $chapter
     * @param Attendance $attendance
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