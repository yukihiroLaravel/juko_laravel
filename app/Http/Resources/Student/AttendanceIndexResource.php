<?php

namespace App\Http\Resources\Student;

use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceIndexResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array $array
     */
    public function toArray($request)
    {
        $array = array();
        foreach ($this->resource as $data) {
            array_push($array, [
                'attendance_id' => $data->id,
                'progress' => $data->progress,
                'course' => [
                    'course_id' => $data->course->id,
                    'title' => $data->course->title,
                    'image' => $data->course->image,
                    'instructor' => [
                        'instructor_id' => $data->course->instructor->id,
                        'nick_name' => $data->course->instructor->nick_name,
                        'last_name' => $data->course->instructor->last_name,
                        'first_name' => $data->course->instructor->first_name,
                        'email' => $data->course->instructor->email,
                    ],
                ]
            ]);
        }
        return $array;
    }
}
