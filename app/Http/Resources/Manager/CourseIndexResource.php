<?php

namespace App\Http\Resources\Manager;

use App\Enums\Course\DeadlineTypeEnum;
use App\Http\Resources\Base\Instructor\CourseResource;
use App\Http\Resources\Base\Instructor\TagResource;
use App\Http\Resources\Base\Student\InstructorResource;
use App\Http\Resources\Base\Instructor\CourseDeadlineResource;
use App\Model\Course;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseIndexResource extends JsonResource
{
    /** @var Course */
    public $resource;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            ...(new CourseResource($this->resource))->toArray($request),
            'instructor' => new InstructorResource($this->resource->instructor),
            'has_active_students' => $this->resource->attendances()->exists(),
            'tags' => TagResource::collection($this->resource['tags']),
            'deadline' => $this->resource->courseDeadline
                ? new CourseDeadlineResource($this->resource->courseDeadline)
                : null,
        ];
    }
}
