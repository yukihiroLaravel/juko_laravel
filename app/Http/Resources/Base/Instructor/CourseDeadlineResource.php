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
        if ($this->fixed_date) {
            return [
                'mode'       => 'fixed_date',
                'fixed_date' => $this->getRawOriginal('fixed_date')
                    ?? substr((string) $this->fixed_date, 0, 10),
            ];
        }

        return $this->relative_days !== null
            ? ['mode' => 'relative', 'relative_days' => (int) $this->relative_days]
            : [];
    }
}
