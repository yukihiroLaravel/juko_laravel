<?php

namespace App\Http\Resources\Instructor;

use App\Http\Resources\Base\Instructor\ChapterResource;
use App\Http\Resources\Base\Instructor\CourseResource;
use App\Http\Resources\Base\Instructor\LessonResource;
use App\Model\Course;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseShowResource extends JsonResource
{
    /** @var Course */
    public $resource;

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
            ...(new CourseResource($this->resource))->toArray($request),
            'chapters' => $this->resource->chapters->map(fn ($chapter) => [
                ...(new ChapterResource($chapter))->toArray($request),
                'lessons' => $chapter->lessons->map(fn ($lesson) => (new LessonResource($lesson))->toArray($request)),
            ]),
        ];
    }
}
