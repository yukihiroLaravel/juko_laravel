<?php

namespace App\Http\Resources\Instructor;

use App\Model\Course;
use App\Model\Tag;
use App\Http\Resources\Course\CourseResource;
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
            'course' => CourseResource::collection($this->resource->courses),
        ];
    }
}
