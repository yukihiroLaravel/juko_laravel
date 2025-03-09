<?php

namespace App\Http\Resources\Tag;

use Illuminate\Http\Resources\Json\ResourceCollection;
use App\Http\Resources\Course\CourseResource;
use Illuminate\Http\Resources\Json\JsonResource;

class TagIndexResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array $array
     */
    public function toArray($request)
    {
        return  [
            'tag_id' => $this->id,
            'content' => $this->content,
            'courses' => CourseResource::collection($this->courses),
        ];
    }
}
