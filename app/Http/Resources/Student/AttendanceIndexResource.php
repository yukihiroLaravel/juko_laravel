<?php

namespace App\Http\Resources\Student;

use Illuminate\Http\Resources\Json\ResourceCollection;

class AttendanceIndexResource extends ResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array $array
     */
    public function toArray($request)
    {
        return $this->collection->map(function ($value) {
            return [
                'attendance_id' => $value->id,
                'course' => [
                    'course_id' => $value->course->id,
                    'title' => $value->course->title,
                    'image' => $value->course->image,
                    'instructor' => [
                        'instructor_id' => $value->course->instructor->id,
                        'nick_name' => $value->course->instructor->nick_name,
                        'last_name' => $value->course->instructor->last_name,
                        'first_name' => $value->course->instructor->first_name,
                        'email' => $value->course->instructor->email,
                        'profile_image' => $value->course->instructor->profile_image,
                    ],
                ],
            ];
        });
    }
}
