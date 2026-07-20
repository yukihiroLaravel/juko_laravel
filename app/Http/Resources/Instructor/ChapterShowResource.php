<?php

namespace App\Http\Resources\Instructor;

use App\Model\Chapter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChapterShowResource extends JsonResource
{
    /** @var Chapter */
    public $resource;

    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    #[\Override]
    public function toArray($request)
    {
        return [
            'chapter_id' => $this->resource->id,
            'title' => $this->resource->title,
            'status' => $this->resource->status,
            'lessons' => $this->resource->lessons->sortBy('order')->map(fn ($lesson) => [
                'lesson_id' => $lesson->id,
                'title' => $lesson->title,
                'url' => $lesson->url,
                'remarks' => $lesson->remarks,
                'status' => $lesson->status,
            ])
                ->values(),
        ];
    }
}
