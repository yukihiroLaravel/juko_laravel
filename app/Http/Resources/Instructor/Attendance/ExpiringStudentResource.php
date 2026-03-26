<?php

namespace App\Http\Resources\Instructor\Attendance;

use App\Model\Student;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpiringStudentResource extends JsonResource
{
    /** @var Student */
    public $resource;

    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'student_id' => $this->resource->id,
            'name' => $this->resource->last_name.' '.$this->resource->first_name,
            'email' => $this->resource->email,
            'expires_at' => $this->resource->expires_at,
            'days_until_expiry' => $this->resource->days_until_expiry,
        ];
    }
}
