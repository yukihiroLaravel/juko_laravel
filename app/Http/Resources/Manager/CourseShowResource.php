<?php

namespace App\Http\Resources\Manager;

use App\Http\Resources\Base\Instructor\ChapterResource;
use App\Http\Resources\Lesson\LessonResource;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'chapters' => $this->resource->chapters->map(function ($chapter) use ($request) {
                return [
                    ...(new ChapterResource($chapter))->toArray($request),
                    'lessons' => LessonResource::collection($chapter->lessons),
                ];
            }),
        ];
    }
}
