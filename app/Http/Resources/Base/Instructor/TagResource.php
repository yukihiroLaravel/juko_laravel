<?php

namespace App\Http\Resources\Base\Instructor;

use App\Model\Tag;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TagResource extends JsonResource
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
            'tag_id' => $this->resource->id,
            'content' => $this->resource->content,
        ];
    }
}
