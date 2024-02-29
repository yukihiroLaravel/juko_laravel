<?php

namespace App\Http\Resources\Manager;

use App\Model\Course;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class StudentIndexResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var \App\Model\Course $course */
        $course = $this->resource['course'];

        /** @var \Illuminate\Pagination\LengthAwarePaginator $data */
        $data = $this->resource['data'];

        return [
            'course' => [
                'course_id' => $course->id,
                'image' => $course->image,
                'title' => $course->title,
            ],
            'pagination' => [
                'page' => $data->currentPage(),
                'total' => $data->total(),
            ],
            'students' => $this->mapStudents($data->getCollection(), $course),
        ];
    }

    private function mapStudents(Collection $results, Course $course)
    {
        return $results->map(function ($result) use ($course) {
            return [
                'student_id' => $result->student_id,
                'nick_name' => $result->nick_name,
                'email' => $result->email,
                'profile_image' => $result->profile_image,
                'course_title' => $course->title,
                'last_login_at' => $result->last_login_at,
                'attendanced_at' => $result->attendanced_at,
            ];
        });
    }
}
