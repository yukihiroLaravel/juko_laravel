<?php

namespace App\Http\Resources\Manager;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

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
        $courses = $this->resource;
        return [
            "courses" => $this->mapCourses($courses),
            "pagination" => [
                "page" => $courses->currentPage(),
                "total" => $courses->total()
            ]
        ];
    }

    /**
     * @param Collection<\App\Model\Notification> $notifications
     * @return array
     */
    private function mapCourses($courses)
    {
        return $courses->map(function ($course) {
            return [
                "course_id" => $course->id,
                "course_title" => $course->title,
                "couese_status" => $course->status
            ];
        });
    }
}
