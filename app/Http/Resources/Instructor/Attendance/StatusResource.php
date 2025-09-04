<?php

namespace App\Http\Resources\Instructor\Attendance;

use App\Http\Resources\Base\Instructor\ChapterResource;
use App\Http\Resources\Base\Instructor\CourseResource;
use App\Http\Resources\Base\Instructor\TagResource;
use App\Model\Attendance;
use Illuminate\Http\Resources\Json\JsonResource;

class StatusResource extends JsonResource
{
    /** @var Attendance */
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
        $courseDeadline = $this->resource->course->courseDeadline;
        
        return [
            'attendance_id' => $this->resource->id,
            'course' => [
                ...(new CourseResource($this->resource->course))->toArray($request),
                'tags' => TagResource::collection($this->resource->course->tags),
                'chapters' => ChapterResource::collection($this->resource->course->chapters),
                'course_deadline' => $courseDeadline ? [
                    'fixed_date' => $this->course->courseDeadline->fixed_date
                        ? \Carbon\Carbon::parse($this->course->courseDeadline->fixed_date)->format('Y-m-d')
                        : null,
                    'relative_days'=> $courseDeadline->relative_days,
                ] : null,
            ],
        ];
    }
}
