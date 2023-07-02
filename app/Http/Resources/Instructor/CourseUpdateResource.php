<?php

namespace App\Http\Resources\Instructor;

use Illuminate\Http\Resources\Json\JsonResource;

class CourseUpdateResource extends JsonResource
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
            'course_id' => $this->resource->id,
            'title' => $this->resource->title,
            'image' => $this->resource->image,
            'status'=> $this->resource->status,
        ];
    }
}
