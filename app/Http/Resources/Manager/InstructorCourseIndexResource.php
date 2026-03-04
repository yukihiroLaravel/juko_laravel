<?php

namespace App\Http\Resources\Manager;

use App\Http\Resources\Base\Instructor\CourseResource;
use App\Http\Resources\Base\Instructor\TagResource;
use App\Model\Course;
use Illuminate\Http\Resources\Json\JsonResource;

class InstructorCourseIndexResource extends JsonResource
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
            ...(new CourseResource($this->resource))->toArray($request),
            'updated_at' => $this->resource->updated_at->format('Y/m/d H:i:s'),
            'tags' => TagResource::collection($this->resource->tags),
            'capacity' => $this->resource->capacity,
            'current_attendance_count' => $this->resource->capacity !== null
                ? ($this->resource->attendances_count ?? 0)
                : null,            
        ];
    }
}
