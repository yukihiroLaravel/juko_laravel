<?php

namespace App\Http\Resources\Manager;

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
        return [
            'course_id' => $this->id,
            'title' => $this->title,
            'status' => $this->status,
            'updated_at' => $this->updated_at->format('Y/m/d H:i:s'),
        ];
    }
}
