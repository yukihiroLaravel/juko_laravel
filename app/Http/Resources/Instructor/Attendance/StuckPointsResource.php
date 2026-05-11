<?php

namespace App\Http\Resources\Instructor\Attendance;

use App\Dto\Instructor\Attendance\StuckPointDto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StuckPointsResource extends JsonResource
{
    /** @var StuckPointDto */
    public $resource;

    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'chapter_id' => $this->resource->id,
            'chapter_title' => $this->resource->title,
            'lessons' => StuckLessonsResource::collection($this->resource->lessons),
        ];
    }
}
