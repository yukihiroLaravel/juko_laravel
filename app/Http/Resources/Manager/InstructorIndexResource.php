<?php

namespace App\Http\Resources\Manager;

use App\Model\Instructor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;

class InstructorIndexResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    #[\Override]
    public function toArray($request)
    {
        /** @var LengthAwarePaginator $data */
        $data = $this->resource;

        return [
            'instructors' => $data->getCollection()->map(fn (Instructor $instructor) => [
                'instructor_id' => $instructor->id,
                'nick_name' => $instructor->nick_name,
                'email' => $instructor->email,
                'profile_image' => $instructor->profile_image,
                'course_count' => $instructor->courses_count ?? 0,
                'student_count' => $instructor->student_count ?? 0,
                'capacity_count' => ($instructor->courses_count ?? 0) === ($instructor->courses_with_capacity_count ?? 0)
                    ? $instructor->capacity_sum
                    : null,
            ]),
            'pagination' => [
                'page' => $data->currentPage(),
                'total' => $data->total(),
            ],
        ];
    }
}
