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
                    'chapter' => $attendance->course->chapters->map(function ($chapter) {
                        return [
                            'chapter_id' => $chapter->id,
                            'title' => $chapter->title,
                            'status' => $chapter->status,
                            'lessons' => $chapter->lessons->map(function ($lesson) use ($chapter) {
                                $lessonAttendance = LessonAttendance::where('lesson_id', $lesson->id)
                                    ->where('attendance_id', $attendance->id)
                                    ->first();
        
                                $progress = 0;
                                if ($lessonAttendance && $lessonAttendance->status === LessonAttendance::STATUS_COMPLETED_ATTENDANCE) {
                                    $progress = 100;
                                }
        
                                return [
                                    'lesson_id' => $lesson->id,
                                    'chapter_id' => $chapter->id,
                                    'status' => $lessonAttendance ? $lessonAttendance->status : null,
                                    'progress' => $progress,
                                ];
                            }),
                        ];
                    }),
                ],
            ],
        ];
    } else {
        return [];
    }
}
}