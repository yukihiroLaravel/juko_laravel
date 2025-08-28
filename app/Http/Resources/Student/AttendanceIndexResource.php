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
        $deadlineDate = $this->resource->calcDeadlineForStudent();

        $setting = optional($this->resource->course->deadline);

        $type = $setting?->fixed_date
            ? DeadlineTypeEnum::FIXED_DATE->value
            : ($setting?->relative_days
                ? DeadlineTypeEnum::RELATIVE_DAYS->value
                : DeadlineTypeEnum::NONE->value);

        return [
            ...(new AttendanceResource($this->resource))->toArray($request),

            'deadline_date' => $deadlineDate?->format('Y-m-d'),
            'expired' => $this->resource->isExpired(),

            'course' => [
                ...(new CourseResource($this->resource->course))->toArray($request),
                'tags' => TagResource::collection($this->resource->course->tags),

                'deadline' => [
                    'type' => $type,
                    'fixed_date' => $setting?->fixed_date?->format('Y-m-d'),
                    'relative_days' => $setting?->relative_days,
                ],
            ],
        ];
    }
}
