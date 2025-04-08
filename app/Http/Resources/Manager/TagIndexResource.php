<?php

namespace App\Http\Resources\Manager;

use App\Http\Resources\Tag\TagResource;
use Illuminate\Http\Resources\Json\JsonResource;

class TagIndexResource extends JsonResource
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
            ...(new TagResource($this->resource))->toArray($request),
            'courses' => CourseIndexResource::collection($this->resource->courses),
        ];
    }
}
