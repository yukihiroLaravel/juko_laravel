<?php

namespace App\Http\Resources\Base\Student;

use App\Model\Attendance;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    /** @var Attendance */
    public $resource;

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array $array
     */
    #[\Override]
    public function toArray($request)
    {
        return [
            'attendance_id' => $this->resource->id,
            'deadline_date' => $this->resource->calcDeadline()?->format('Y-m-d'),
            'expired'       => $this->resource->isExpired(),
        ];
    }
}
