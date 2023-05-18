<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CourseShowResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'course_id' => $this->resource->course->id,
            'title' => $this->resource->course->title,
            'image' => $this->resource->course->image,
            'instructor' => $this->instructor(),
            'attendance' => $this->attendance(),
            'chapters' => $this->chapters(),
        ];
    }

    private function instructor()
    {
        return [
            'instructor_id' => $this->resource->course->instructor->id,
            'nick_name' => $this->resource->course->instructor->nick_name,
            'last_name' => $this->resource->course->instructor->last_name,
            'first_name' => $this->resource->course->instructor->first_name,
            'email' => $this->resource->course->instructor->email,
        ];
    }

    private function attendance()
    {
        return [
            'attendance_id' => $this->resource->id,
            'progress' => $this->resource->progress,
        ];
    }

    private function chapters()
    {
        return $this->resource->course->chapters->map(function($chapter) {
            return [
                'chapter_id' => $chapter->id,
                'title' => $chapter->title,
                'lessons' => $this->lessons($chapter->lessons),
            ];
        });
    }

    private function lessons($lessons)
    {
        return $lessons->map(function($lesson) {
            $lessonAttendance = $this->resource->lessonAttendances->filter(function ($lessonAttendance) use ($lesson) {
                return $lesson->id === $lessonAttendance->lesson_id;
            })->first();
            return [
                'lesson_id' => $lesson->id,
                'title' => $lesson->title,
                'url' => $lesson->url,
                'remarks' => $lesson->remarks,
                'lessonAttendance' => [
                    'lesson_attendance_id' => $lessonAttendance->id,
                    'status' => $lessonAttendance->status,
                ]
            ];
        });
    }
}
