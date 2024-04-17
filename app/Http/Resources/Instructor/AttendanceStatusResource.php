<?php

namespace App\Http\Resources\Instructor;

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

        return [
            'attendance_id' => $attendance->id,
                'progress' => $attendance->progress,
                'course' => [
                    'course_id' => $attendance->course->id,
                    'title' => $attendance->course->title,
                    'status' => $attendance->course->status,
                    'image' => $attendance->course->image,
                    'chapters' => $attendance->course->chapters->map(function (Chapter $chapter) {
                        return [
                            'chapter_id' => $chapter->id,
                            'title' => $chapter->title,
                            'status' => $chapter->status,
                            'progress' => $attendance->progress,
                        ];
                    }),
                ],
        ];
    }
}