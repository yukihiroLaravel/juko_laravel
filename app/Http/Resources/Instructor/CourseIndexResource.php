<?php

namespace App\Http\Resources\Instructor;

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
                'course_id' => $course->id,
                'image' => $course->image,
                'title' => $course->title,
                'status' => $course->status,
            ];
        });
    }
}
