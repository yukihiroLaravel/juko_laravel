<?php

namespace App\Http\Resources\Student;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoginHistoryResource extends JsonResource
{
    /** @var \App\Model\StudentLoginHistory */
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
            'student_login_history_id' => $this->resource->id,
            'logged_in_at' => $this->resource->logged_in_at,
        ];
    }
}
