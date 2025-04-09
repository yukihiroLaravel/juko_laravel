<?php

namespace App\Http\Resources\Manager;

use App\Model\Course;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseIndexResource extends JsonResource
{
    /** @var Course */
    public $resource;

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'course_id' => $this->resource->id,
            'title' => $this->resource->title,
            'image' => $this->resource->image,
            'status' => $this->resource->status,
            'instructor' => [
                'instructor_id' => $this->resource->instructor_id,
                'nick_name' => $this->resource->instructor->nick_name,
                'last_name' => $this->resource->instructor->last_name,
                'first_name' => $this->resource->instructor->first_name,
                'email' => $this->resource->instructor->email,
                'profile_image' => $this->resource->instructor->profile_image,
            ],
            'has_active_students' => $this->resource->has_active_students,
        ];
    }
}
