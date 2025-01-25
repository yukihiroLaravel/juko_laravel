<?php

namespace App\Http\Resources\Instructor;

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
            'pagination' => [
                'page' => $data->currentPage(),
                'total' => $data->total(),
            ],
            'students' => $this->mapStudents($data->getCollection()),
        ];
    }

    private function mapStudents(Collection $results)
    {
        return $results->map(function ($result) {
            return [
                'student_id' => $result->student_id,
                'nick_name' => $result->nick_name,
                'email' => $result->email,
                'last_login_at' => $result->last_login_at,
                'attendance' => [
                    'attendance_id' => $result->attendance_id,
                    'attendanced_at' => $result->attendanced_at,
                ],
            ];
        });
    }
}
