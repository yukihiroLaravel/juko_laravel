<?php

namespace App\Http\Resources\Instructor\Attendance;

use Illuminate\Http\Request;
use Carbon\CarbonImmutable;
use Illuminate\Http\Resources\Json\JsonResource;

class FollowUpResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $lastLogin = $this->latest_login_at;

        return [
            'student_id'       => $this->id,
            'name'             => $this->last_name . ' ' . $this->first_name,
            'email'            => $this->email,
            'last_login_at'    => $lastLogin
                ? CarbonImmutable::parse($lastLogin)->toIso8601String()
                : null,
            'days_since_login' => $lastLogin
                ? (int) CarbonImmutable::parse($lastLogin)->diffInDays(CarbonImmutable::now())
                : null,
        ];
    }
}
