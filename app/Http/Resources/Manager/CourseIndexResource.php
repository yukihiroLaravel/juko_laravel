<?php

namespace App\Http\Resources\Manager;

use App\Http\Resources\Base\Instructor\CourseResource;
use App\Http\Resources\Base\Student\InstructorResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Base\Instructor\TagResource;

class CourseIndexResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...(new CourseResource($this->resource))->toArray($request),
            'instructor' => new InstructorResource($this->resource->instructor),
            'has_active_students' => $this->resource->attendances()->exists(),
            'tags' => TagResource::collection($this->resource['tags']),
        ];
    }
}
