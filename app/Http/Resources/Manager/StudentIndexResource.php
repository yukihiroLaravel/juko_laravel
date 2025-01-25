<?php

namespace App\Http\Resources\Manager;

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
                'profile_image' => $result->profile_image,
                'last_login_at' => $result->last_login_at,
                'attendance' => [
                    'attendance_id' => $result->attendance_id,
                    'attendanced_at' => $result->attendanced_at,
                ],
            ];
        });
    }
}
