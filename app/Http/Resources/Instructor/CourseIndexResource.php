<?php

namespace App\Http\Resources\Instructor;

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
            'image' => $this->resource->image,
            'title' => $this->resource->title,
            'status' => $this->resource->status,
            'has_active_students' => (bool) $this->resource->has_active_students,
            'per_page' => ['integer', 'min:1', 'max:100'],
            'page' => ['integer', 'min:1'],
        ];
    }
}
