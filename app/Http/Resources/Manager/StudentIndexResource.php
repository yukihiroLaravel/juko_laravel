<?php

namespace App\Http\Resources\Manager;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class StudentIndexResource extends JsonResource
{
    /** @var LengthAwarePaginator */
    public $resource;

    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    #[\Override]
    public function toArray($request)
    {
        return [
            'pagination' => [
                'page' => $this->resource->currentPage(),
                'total' => $this->resource->total(),
            ],
            'students' => $this->mapStudents($this->resource->getCollection()),
        ];
    }

    private function mapStudents(Collection $results)
    {
        return $results->map(fn ($result) => [
            'student_id' => $result->student_id,
            'nick_name' => $result->nick_name,
            'email' => $result->email,
            'profile_image' => $result->profile_image,
            'last_login_at' => $result->last_login_at,
            'attendance' => [
                'attendance_id' => $result->attendance_id,
                'attendanced_at' => $result->attendanced_at,
            ],
        ]);
    }
}
