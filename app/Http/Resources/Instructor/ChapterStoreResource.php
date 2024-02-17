<?php

namespace App\Http\Resources\Instructor;

use App\Model\Chapter;
use Illuminate\Http\Resources\Json\JsonResource;

class ChapterStoreResource extends JsonResource
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
            'course_id' => $this->resource->course_id,
            'chapter' => [
                'chapter_id' => $this->resource->id,
                'title' => $this->resource->title,
            ]
        ];
    }
}
