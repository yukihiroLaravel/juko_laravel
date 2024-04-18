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
                            'progress' => $attendance->progress,
                            'lessons' => $chapter->lessons->map(function ($lesson) use ($attendance) {

                                $lessonAttendance = LessonAttendance::where('lesson_id', $lesson->id)
                                                                    ->where('attendance_id', $attendance->id)
                                                                    ->first();
                                return [
                                    'lesson_id' => $lesson->id,
                                    'title' => $lesson->title,
                                    'status' => $lessonAttendance ? $lessonAttendance->status : null,
                                    'progress' => $lessonAttendance && $lessonAttendance->status === LessonAttendance::STATUS_COMPLETED_ATTENDANCE ? 100 : 0,
                                ];
                            }),
                        ];
                    }),
                ],
            ];
        } else {
            return [];
        }
    }
}