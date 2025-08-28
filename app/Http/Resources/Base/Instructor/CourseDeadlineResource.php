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
        // 固定日
        if ($this->resource->fixed_date) {
            return [
                'mode'       => 'fixed_date',
                'fixed_date' => $this->resource->getRawOriginal('fixed_date')
                    ?? substr((string) $this->resource->fixed_date, 0, 10),
            ];
        }

        // 相対日数
        if ($this->resource->relative_days !== null) {
            return [
                'mode'          => 'relative',
                'relative_days' => (int) $this->resource->relative_days,
            ];
        }

        // 期限なし（このリソース単体は空）
        return [];
    }
}
