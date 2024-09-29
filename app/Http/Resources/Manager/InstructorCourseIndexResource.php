<?php

namespace App\Http\Resources\Manager;

use App\Model\Course;
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
            "courses" => $this->mapCourses($courses->getCollection()),
            "pagination" => [
                "page" => $courses->currentPage(),
                "total" => $courses->total()
            ]
        ];
    }

    /**
     * @param Collection<\App\Model\Course> $courses
     * @return array
     */
    private function mapCourses(Collection $courses)
    {
        return $courses->map(function (Course $course) {
            return [
                "course_id" => $course->id,
                "title" => $course->title,
                "status" => $course->status,
            ];
        })
        ->toArray();
    } 
}
