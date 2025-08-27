<?php

namespace App\Http\Resources\Base\Instructor;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Model\CourseDeadline;

class CourseDeadlineResource extends JsonResource
{
    /** @var CourseDeadline */
    public $resource;

    #[\Override]
    public function toArray($request)
    {
        return [
            'fixed_date' => optional($this->resource->fixed_date)?->toDateString(),
            'relative_days' => $this->resource->relative_days,
        ];
    }
}