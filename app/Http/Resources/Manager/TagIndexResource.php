<?php

namespace App\Http\Resources\Manager;

use App\Http\Resources\Base\Instructor\TagResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TagIndexResource extends JsonResource
{
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
            ...(new TagResource($this->resource))->toArray($request),
            'courses' => CourseIndexResource::collection($this->resource->courses),
        ];
    }
}
