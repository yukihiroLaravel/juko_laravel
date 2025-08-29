<?php

namespace App\Http\Resources\Base\Instructor;

use App\Model\CourseDeadline;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseDeadlineResource extends JsonResource
{
    /** @var CourseDeadline */
    public $resource;

    #[\Override]
    public function toArray($request)
    {
        return [
            'course_deadline_id' => $this->resource->id,
            'fixed_date' => $this->resource->fixed_date?->format('Y-m-d'),
            'relative_days' => $this->resource->relative_days,
        ];
    }
}
