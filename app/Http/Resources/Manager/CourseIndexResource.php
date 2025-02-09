<?php

namespace App\Http\Resources\Manager;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class CourseIndexResource extends JsonResource
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
            'pagination' => [
                'current_page' => $this->resource->currentPage(),
                'total' => $this->resource->total(),
                'per_page' => $this->resource->perPage(),
                'last_page' => $this->resource->lastPage(),
                'next_page_url' => $this->resource->nextPageUrl(),
                'prev_page_url' => $this->resource->previousPageUrl(),
            ],
            'courses' => $this->mapCourses($this->resource->getCollection()),
        ];
    }

    private function mapCourses(Collection $courses)
    {
        return $courses->map(function ($course) {
            return [
                'course_id' => $course->id,
                'title' => $course->title,
                'image' => $course->image,
                'status' => $course->status,
                'instructor' => [
                    'instructor_id' => $course->instructor_id,
                    'nick_name' => $course->instructor->nick_name,
                    'last_name' => $course->instructor->last_name,
                    'first_name' => $course->instructor->first_name,
                    'email' => $course->instructor->email,
                    'profile_image' => $course->instructor->profile_image,
                ],
            ];
        });
    }
}
