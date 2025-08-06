<?php

namespace App\Http\Resources\Instructor;

use App\Http\Resources\Base\Instructor\ChapterResource;
use App\Http\Resources\Base\Instructor\LessonResource;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseShowResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    #[\Override]
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'attendance_deadline' => $this->attendance_deadline 
                ? $this->attendance_deadline->format('Y-m-d') 
                : null,
            
            'chapters' => $this->resource->chapters->map(fn ($chapter) => [
                new ChapterResource($chapter),
                'lessons' => LessonResource::collection($chapter->lessons),
            ]),
        ];
    }
}