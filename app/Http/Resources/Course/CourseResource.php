<?php

namespace App\Http\Resources\Course;

use App\Http\Resources\Instructor\InstructorResource;
use App\Model\Course;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    /** @var Course */
    public $resource;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'course_id' => $this->resource->id,
            'title' => $this->resource->title,
            'image' => $this->resource->image,
            'status' => $this->resource->status,
            'instructor' => new InstructorResource($this->resource->instructor),
            'has_active_students' => $this->resource->has_active_students,
        ];
    }
}
