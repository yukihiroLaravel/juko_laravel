<?php

namespace App\Http\Resources\Instructor\Attendance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpiringResource extends JsonResource
{
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'days' => $this->resource['days'],
            'students' => ExpiringStudentResource::collection($this->resource['students']),
        ];
    }
}
