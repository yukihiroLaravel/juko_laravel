<?php

namespace App\Http\Resources\Instructor\Attendance;

use App\Dto\Instructor\Attendance\StuckLessonDto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StuckLessonsResource extends JsonResource
{
    /** @var StuckLessonDto */
    public $resource;

    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'lesson_id' => $this->resource->id,
            'lesson_title' => $this->resource->title,
        ];
    }
}
