<?php

namespace App\Http\Resources\Student;

use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\Lesson;
use App\Model\LessonAttendance;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceShowChapterResource extends JsonResource
{
    #[\Override]
    public function toArray($request)
    {
        /** @var Attendance $attendance */
        $attendance = $this->resource['attendance'];

        /** @var Chapter $chapter */
        $chapter = $this->resource['chapter'];

        return [
            'attendance_id' => $attendance->id,
            'course' => [
                'course_id' => $attendance->course->id,
                'title' => $attendance->course->title,
                'image' => $attendance->course->image,
                'tags' => $attendance->course->tags,
                'chapter' => [
                    'chapter_id' => $chapter->id,
                    'title' => $chapter->title,
                    'lessons' => $chapter->lessons->map(function (Lesson $lesson) {
                        $lessonAttendance = $lesson->lessonAttendances->filter(fn ($lessonAttendance) => $lessonAttendance->lesson_id === $lesson->id)
                            ->map(fn (LessonAttendance $lessonAttendance) => [
                                'lesson_attendance_id' => $lessonAttendance->id,
                                'status' => $lessonAttendance->status,
                            ])
                            ->first();

                        return [
                            'lesson_id' => $lesson->id,
                            'title' => $lesson->title,
                            'completed_lessons_count' => $lesson->completed_lessons_count,
                            'total_lessons_count' => $lesson->total_lessons_count,
                            'url' => $lesson->url,
                            'remarks' => $lesson->remarks,
                            'lessonAttendance' => $lessonAttendance,
                        ];
                    }),
                ],
            ],
        ];
    }
}
