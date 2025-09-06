<?php

namespace App\Http\Resources\Instructor;

use App\Http\Resources\Base\Instructor\AttendanceResource;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceShowResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    #[\Override]
    public function toArray($request)
    {
        return [
            ...(new AttendanceResource($this->resource['attendance']))->toArray($request),
            'students_count' => $this->resource['studentsCount'],
        ];
    }
}
