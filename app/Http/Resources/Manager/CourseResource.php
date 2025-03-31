<?php

namespace App\Http\Resources\Manager;

use App\Http\Resources\Course\CourseResource as BaseCourseResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...(new BaseCourseResource($this->resource))->toArray($request),
            'has_active_students' => $this->resource->attendances()->exists(),
        ];
    }
}
