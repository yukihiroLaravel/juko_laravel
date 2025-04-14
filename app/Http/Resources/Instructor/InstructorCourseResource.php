<?php

namespace App\Http\Resources\Instructor;

use App\Http\Resources\Base\Instructor\CourseResource;
use App\Model\Course;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstructorCourseResource extends JsonResource
{
    /** @var Course */
    public $resource;

    public function toArray(Request $request): array
    {
        return [
            ...(new CourseResource($this->resource))->toArray($request),
            'has_active_students' => $this->resource->attendances()->exists(),
        ];
    }
}
