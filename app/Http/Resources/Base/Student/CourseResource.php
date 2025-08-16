<?php

namespace App\Http\Resources\Base\Student;

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
            'attendance_deadline' => $this->resource->attendance_deadline
                ? $this->resource->attendance_deadline->format('Y-m-d H:i:s')
                : null,
        ];
    }
}
