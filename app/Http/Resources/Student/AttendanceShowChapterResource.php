<?php

namespace App\Http\Resources\Student;

use App\Model\Lesson;
use App\Model\Chapter;
use App\Model\Attendance;
use App\Model\LessonAttendance;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceShowChapterResource extends JsonResource
{
    public function toArray($request)
    {
        /** @var Attendance $attendance */
        $attendance = $this->resource['attendance'];

        /** @var Chapter $chapter */
        $chapter = $this->resource['chapter'];

        return [
            'attendance_id' => $attendance->id,
            'progress' => $attendance->progress,
            'course' => [
                'course_id' => $attendance->course->id,
                'title' => $attendance->course->title,
                'image' => $attendance->course->image,
                'chapter' => [
                    'chapter_id' => $chapter->id,
                    'title' => $chapter->title,
                    'lessons' => $chapter->lessons->map(function(Lesson $lesson) {
                        $lessonAttendance = $lesson->lessonAttendances->filter(function ($lessonAttendance) use($lesson) {
                            return $lessonAttendance->lesson_id === $lesson->id;
                        })
                        ->map(function(LessonAttendance $lessonAttendance) {
                            return [
                                'lesson_attendance_id' => $lessonAttendance->id,
                                'status' => $lessonAttendance->status,
                            ];
                        })
                        ->first();

                        return [
                            'lesson_id' => $lesson->id,
                            'title' => $lesson->title,
                            'completed_lessons_count' => $lesson->completed_lessons_count,
                            'total_lessons_count' => $lesson->total_lessons_count,
                            'url' => $lesson->url,
                            'remarks' => $lesson->remarks,
                            'lessonAttendance' => $lessonAttendance
                        ];
                    }),
                ],
            ],
        ];
    }
}
