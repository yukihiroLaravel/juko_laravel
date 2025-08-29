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

            'deadline_type' => $this->resource->courseDeadline?->fixed_date
                ? 'fixed_date'
                : ($this->resource->courseDeadline?->relative_days !== null
                    ? 'relative'
                    : null),
                    
            'course_deadline' => $this->resource->courseDeadline
                ? ($this->resource->courseDeadline->fixed_date
                    ?? $this->resource->courseDeadline->relative_days)
                : null,
        ];
    }
}
