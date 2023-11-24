<?php

namespace App\Http\Resources\Manager;

use Illuminate\Http\Resources\Json\JsonResource;

class CourseIndexResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->resource->map(function($course) {
            return [
                "course_id" => $course->id,
                "title" => $course->title,
                "image" => $course->image,
                "status" => $course->status,
                "instructor" => [
                  "instructor_id" => $course->instructor_id,
                  "nick_name" => $course->instructor->nick_name,
                  "last_name" => $course->instructor->last_name,
                  "first_name" => $course->instructor->first_name,
                  "email" => $course->instructor->email,
                  "profile_image" => $course->instructor->profile_image,
                ]
            ];
        });
    }
}
