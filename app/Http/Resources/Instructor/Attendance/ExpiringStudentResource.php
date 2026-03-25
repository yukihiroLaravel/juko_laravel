<?php

namespace App\Http\Resources\Instructor\Attendance;

use App\Model\Student;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpiringStudentResource extends JsonResource
{
    /** @var Student */
    public $resource;

    #[\Override]
    public function toArray(Request $request): array
    {
        $deadline = $this->resource->attendance_deadline
            ? CarbonImmutable::parse($this->resource->attendance_deadline)
            : null;

        return [
            'student_id' => $this->resource->id,
            'name' => $this->resource->last_name.' '.$this->resource->first_name,
            'email' => $this->resource->email,
            'expires_at' => $deadline?->format('Y-m-d'),
            'days_until_expiry' => $deadline
                ? (int) CarbonImmutable::now()->diffInDays($deadline)
                : null,
        ];
    }
}