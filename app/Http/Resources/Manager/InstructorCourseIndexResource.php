<?php

namespace App\Http\Resources\Manager;

use App\Model\Course;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Http\Resources\Course\CourseResource;

class InstructorCourseIndexResource extends JsonResource
{
    /** @var LengthAwarePaginator */
    public $resource;

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            ...(new CourseResource($this->resource))->toArray($request),
            'updated_at' => $this->resource->updated_at->format('Y/m/d H:i:s'),
        ];
    }
}
