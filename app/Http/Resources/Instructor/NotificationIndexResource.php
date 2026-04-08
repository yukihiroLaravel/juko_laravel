<?php

namespace App\Http\Resources\Instructor;

use App\Http\Resources\Base\Instructor\CourseResource;
use App\Http\Resources\Base\Instructor\NotificationResource;
use App\Http\Resources\Base\Instructor\TagResource;
use App\Model\Notification;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationIndexResource extends JsonResource
{
    /** @var Notification */
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
            ...(new NotificationResource($this->resource))->toArray($request),
            'course' => [
                ...(new CourseResource($this->resource->course))->toArray($request),
                'current_attendance_count' => $this->resource->course->capacity !== null
                    ? ($this->resource->course->current_attendance_count ?? 0)
                    : null,
            ],
            'tags' => TagResource::collection($this->resource->course->tags),
        ];
    }
}
