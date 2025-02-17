<?php

namespace App\Http\Resources\Manager;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Chapter\ChapterResource;
use App\Http\Resources\Lesson\LessonResource;

class CourseShowResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'chapters' => $this->resource->chapters->sortBy('order')->map(function ($chapter) {
                return [
                    new ChapterResource($chapter),
                    'lessons' => LessonResource::collection($chapter->lessons),
                ];
            }),
        ];
    }
}
