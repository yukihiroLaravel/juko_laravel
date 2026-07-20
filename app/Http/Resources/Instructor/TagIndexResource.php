<?php

namespace App\Http\Resources\Instructor;

use App\Http\Resources\Base\Instructor\CourseResource;
use App\Http\Resources\Base\Instructor\TagResource;
use App\Model\Tag;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TagIndexResource extends JsonResource
{
    /** @var Tag */
    public $resource;

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
            'courses' => CourseResource::collection($this->resource->courses),
        ];
    }
}
