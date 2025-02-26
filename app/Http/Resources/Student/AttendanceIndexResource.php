<?php

namespace App\Http\Resources\Student;

use App\Http\Resources\Course\CourseResource;
use App\Model\Attendance;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

class AttendanceIndexResource extends ResourceCollection
{
    /** @var LengthAwarePaginator<Attendance> */
    public $resource;

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array $array
     */
    public function toArray($request)
    {
        return $this->resource->getCollection()->map(function (Attendance $value) use ($request) {
            return [
                'attendance_id' => $value->id,
                'course' => [
                    ...(new CourseResource($value->course))->toArray($request),
                    'progress_percentage' => $value->course->progress_percentage,
                ],
            ];
        })
            ->toArray();
    }
}
