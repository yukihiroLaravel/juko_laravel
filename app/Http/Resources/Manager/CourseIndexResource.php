<?php

namespace App\Http\Resources\Manager;

use App\Http\Resources\Base\Instructor\CourseResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'has_active_students' => $this->resource->attendances()->exists(),
        ];
    }
}
