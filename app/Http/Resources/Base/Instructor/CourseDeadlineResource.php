<?php

namespace App\Http\Resources\Base\Instructor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseDeadlineResource extends JsonResource
{
    /** @var \App\Model\CourseDeadline */
    public $resource;

    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'course_deadline_id' => $this->resource->id,
            'fixed_date' => $this->resource->fixed_date
                ? ($this->resource->getRawOriginal('fixed_date')
                    ?? substr((string) $this->resource->fixed_date, 0, 10))
                : null,
            'relative_days' => $this->resource->relative_days !== null
                ? (int) $this->resource->relative_days
                : null,
        ];
    }
}
