<?php

namespace App\Http\Resources;

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
        return $this->resource->map(function($value, $key) {
            return [
                'course_id' => $value->course->id,
                'title' => $value->course->title,
                'image' => $value->course->image,
                'instructor' => [
                    'instructor' => $value->course->instructor->id,
                    'nick_name' => $value->course->instructor->nick_name,
                    'last_name' => $value->course->instructor->last_name,
                    'first_name' => $value->course->instructor->first_name,
                    'email' => $value->course->instructor->email,
                    'profile_image' => $value->course->instructor->profile_image,
                ],
                'attendance' => [
                    'attendance_id' => $value->id,
                    'progress' => $value->progress,
                ],
            ];
        });
    }
}
