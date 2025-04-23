<?php

namespace App\Http\Resources\Base\Student;

use App\Model\Chapter;
use Illuminate\Http\Resources\Json\JsonResource;

class ChapterResource extends JsonResource
{
    /** @var Chapter */
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
            'chapter_id' => $this->resource->id,
            'title' => $this->resource->title,
            'order' => $this->resource->order,
        ];
    }
}
