<?php

namespace App\Http\Resources\Manager;

use App\Http\Resources\Tag\TagResource;
use App\Model\Course;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\JsonResource;
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
            'courses' => $this->mapCourses($courses->getCollection()),
            'pagination' => [
                'page' => $courses->currentPage(),
                'total' => $courses->total(),
            ],
        ];
    }

    /**
     * @param  Collection<int, Course>  $courses
     * @return array
     */
    private function mapCourses(Collection $courses)
    {
        return $courses->map(function (Course $course) {
            return [
                'course_id' => $course->id,
                'title' => $course->title,
                'status' => $course->status,
                'updated_at' => $course->updated_at->format('Y/m/d H:i:s'),
                'tags' => TagResource::collection($course->tags),
            ];
        })
            ->toArray();
    }
}
