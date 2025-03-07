<?php

namespace App\Http\Resources\Tag;

use Illuminate\Http\Resources\Json\ResourceCollection;
use App\Http\Resources\Course\CourseResource;

class TagIndexResource extends ResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array $array
     */
    public function toArray($request)
    {
        return $this->collection->map(function ($value) {
            return[
                'tag_id' => $value->id,
                'content' => $value->content,
                'courses' => CourseResource::collection($value->courses),
            ];
        });
    }
}