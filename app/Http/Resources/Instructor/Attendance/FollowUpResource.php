<?php

namespace App\Http\Resources\Instructor\Attendance;

use App\Dto\Instructor\Attendance\FollowUpStudentDto;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FollowUpResource extends JsonResource
{
    /** @var FollowUpStudentDto */
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
            'student_id' => $this->resource->studentId,
            'name' => $this->resource->lastName.' '.$this->resource->firstName,
            'email' => $this->resource->email,
            'last_login_at' => $this->resource->latestLoginAt,
            'days_since_login' => $this->resource->latestLoginAt
                ? (int) CarbonImmutable::parse($this->resource->latestLoginAt)->diffInDays(CarbonImmutable::now())
                : null,
            'incomplete_chapter' => $this->resource->incompleteChapterId !== null
                ? [
                    'id' => $this->resource->incompleteChapterId,
                    'title' => $this->resource->incompleteChapterTitle,
                ]
                : null,
        ];
    }
}
