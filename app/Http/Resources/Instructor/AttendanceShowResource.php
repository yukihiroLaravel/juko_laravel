<?php

namespace App\Http\Resources\Instructor;

use App\Http\Resources\Base\Instructor\AttendanceResource;
use App\Http\Resources\Base\Instructor\CourseResource;
use App\Http\Resources\Instructor\Attendance\ChapterCompletedStudentsCountResource;
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
            'course' => new CourseResource($this->resource['attendance']->course),
            'students_count' => $this->resource['studentsCount'],
            'chapters' => ChapterCompletedStudentsCountResource::collection($this->resource['chapters']),
        ];
    }
}
