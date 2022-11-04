<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CourseGetResponse extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->resource->map(function($value, $key) {
            return [
                'course_id' => $value->course->id,
                'title' => $value->course->title,
                'image' => $value->course->image,
                'chapter' => [
                    'chapter_id' => $value->course->chapter->id,
                    'title' => $value->course->chapter->title,
                    'lesson' => [
                        'lesson_id' => $value->course->chapter->lesson->id,
                        'title' => $value->course->chapter->lesson->title,
                        'status' => $value->course->chapter->lesson->status,
                        'lesson_attendance' => [
                            'lesson_attendance_id' =>  $value->course->chapter->lesson->lesson_attendance->id,
                            'status' => $value->course->chapter->lesson->lesson_attendance->status,
                        ],
                    ],
                ],
                'attendance' => [
                    'attendance_id' => $value->id,
                    'progress' => $value->progress,
                ],
            ];
        });
    }
}