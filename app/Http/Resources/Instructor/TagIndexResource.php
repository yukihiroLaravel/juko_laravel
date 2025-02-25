<?php

namespace App\Http\Resources\Instructor;

use App\Model\Course;
use App\Model\Tag;
use Illuminate\Http\Resources\Json\JsonResource;

class TagIndexResource extends JsonResource
{
    /** @var Tag */
    public $resource;

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'tag_id' => $this->resource->id,
            'content' => $this->resource->content,
            'course' => $this->resource->courses->map(function (Course $course) {
                return [
                    'course_id' => $course->id,
                    'title' => $course->title,
                    'image' => $course->image,
                    'status' => $course->status,
                ];
            }),
        ];
    }
}
