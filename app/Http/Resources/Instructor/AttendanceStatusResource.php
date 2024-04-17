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
        $course = $this->course;

        return [
            'attendance_id' => $this->id,
            'course' => [
                'course_id' => $course->id,
                'title' => $course->title,
                'progress' => $this->progress,
                'chapters' => $course->chapters->map(function ($chapter) {
                    return [
                        'chapter_id' => $chapter->id,
                        'title' => $chapter->title,
                        'progress' => $chapter->progress,
                    ];
                }),
            ],
        ];
    }
}