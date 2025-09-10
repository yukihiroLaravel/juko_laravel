<?php

namespace App\Http\Resources\Instructor;

use App\Http\Resources\Base\Instructor\AttendanceResource;
use App\Http\Resources\Base\Instructor\CourseDeadlineResource;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceShowResource extends JsonResource
{
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
            ...(new AttendanceResource($this->resource['attendance']))->toArray($request),
            'students_count' => $this->resource['studentsCount'],
            'course_deadline' => $this->resource['course_deadline']
                ? new CourseDeadlineResource($this->resource['course_deadline'])
                : null,
        ];
    }
}
