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
            'attendance_deadline' => $this->resource->attendance_deadline,
            'has_deadline' => !is_null($this->resource->attendance_deadline),
            'is_expired' => $this->resource->attendance_deadline
                ? now()->gt($this->resource->attendance_deadline)
                : false,
        ];
    }
}
