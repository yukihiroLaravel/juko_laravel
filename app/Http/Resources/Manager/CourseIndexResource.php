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
        return [
            'course_id' => $this->id,
            'title' => $this->title,
            'image' => $this->image,
            'status' => $this->status,
            'instructor' => [
                'instructor_id' => $this->instructor_id,
                'nick_name' => $this->instructor->nick_name,
                'last_name' => $this->instructor->last_name,
                'first_name' => $this->instructor->first_name,
                'email' => $this->instructor->email,
                'profile_image' => $this->instructor->profile_image,
            ],
            'has_active_students' => $this->attendances()->exists(),
        ];
    }
}
