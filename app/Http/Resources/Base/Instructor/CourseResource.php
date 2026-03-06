<?php

namespace App\Http\Resources\Base\Instructor;

use App\Model\Course;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    /** @var Course */
    public $resource;

    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'course_id' => $this->resource->id,
            'title' => $this->resource->title,
            'image' => $this->resource->image,
            'status' => $this->resource->status,
            'deadline_type' => $this->resource->deadline_type,
            'capacity' => $this->resource->capacity, // 定員人数を追記
            'course_deadline' => $this->resource->courseDeadline
                ? new CourseDeadlineResource($this->resource->courseDeadline)
                : null,
        ];
    }
}
