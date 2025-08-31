<?php

namespace App\Http\Resources\Student;

use App\Enums\Course\DeadlineTypeEnum;
use App\Http\Resources\Base\Student\AttendanceResource;
use App\Http\Resources\Base\Student\CourseResource;
use App\Http\Resources\Base\Student\TagResource;
use App\Model\Attendance;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceIndexResource extends JsonResource
{
    /** @var Attendance */
    public $resource;

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array $array
     */
    #[\Override]
    public function toArray($request): array
    {
        return [
            ...(new AttendanceResource($this->resource))->toArray($request),

            'course' => [
                ...(new CourseResource($this->resource->course))->toArray($request),
                'tags' => TagResource::collection($this->resource->course->tags),
                'deadline_type' => $this->resource->course->deadline_type ?? 'none',
            ],
        ];
    }
}
