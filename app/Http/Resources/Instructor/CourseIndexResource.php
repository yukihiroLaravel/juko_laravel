<?php

namespace App\Http\Resources\Instructor;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Model\Attendance;

class CourseIndexResource extends JsonResource
{
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
                'image' => $this->image,
                'title' => $this->title,
                'status' => $this->status,
                'has_active_students' => (bool) $this->has_active_students,
            ];
    }
}
