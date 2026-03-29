<?php

namespace App\Http\Resources\Instructor\Attendance;

use App\Model\Student;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FollowUpResource extends JsonResource
{
    /** @var Student */
    public $resource;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'student_id' => $this->resource->id,
            'name' => $this->resource->last_name.' '.$this->resource->first_name,
            'email' => $this->resource->email,
            'last_login_at' => $this->resource->latest_login_at
                ? CarbonImmutable::parse($this->resource->latest_login_at)->toDateTimeString()
                : null,
            'days_since_login' => $this->resource->latest_login_at
                ? (int) CarbonImmutable::parse($this->resource->latest_login_at)->diffInDays(CarbonImmutable::now())
                : null,
            'incomplete_chapter_name' => $this->resource->incomplete_chapter_name,
        ];
    }
}
