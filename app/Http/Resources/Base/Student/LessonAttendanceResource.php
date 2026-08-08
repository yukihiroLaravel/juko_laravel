<?php

namespace App\Http\Resources\Base\Student;

use App\Model\LessonAttendance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonAttendanceResource extends JsonResource
{
    /** @var LessonAttendance */
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
            'lesson_attendance_id' => $this->resource->id,
            'status' => $this->resource->status,
        ];
    }
}
