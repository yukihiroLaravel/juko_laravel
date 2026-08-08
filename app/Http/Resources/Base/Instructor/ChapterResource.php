<?php

namespace App\Http\Resources\Base\Instructor;

use App\Model\Chapter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChapterResource extends JsonResource
{
    /** @var Chapter */
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
            'chapter_id' => $this->resource->id,
            'title' => $this->resource->title,
            'order' => $this->resource->order,
            'status' => $this->resource->status,
        ];
    }
}
