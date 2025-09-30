<?php

namespace App\Http\Resources\Instructor;

use App\Http\Resources\Base\Instructor\TagResource;
use App\Http\Resources\Base\Instructor\CourseDeadlineResource;
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
    #[\Override]
    public function toArray($request)
    {
        return [
            'course_id' => $this->resource->id,
            'image' => $this->resource->image,
            'title' => $this->resource->title,
            'status' => $this->resource->status,
            'has_active_students' => (bool) $this->resource->has_active_students,
            'tags' => TagResource::collection($this->resource->tags),
            'course_deadline' => $this->resource->courseDeadline
                ? new CourseDeadlineResource($this->resource->courseDeadline)
                : null,
        ];
    }
}
