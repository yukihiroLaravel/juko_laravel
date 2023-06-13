<?php

namespace App\Http\Resources\Instructor;

use Illuminate\Http\Resources\Json\JsonResource;

class LessonUpdateResource extends JsonResource
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
            'lesson_id' => $this->resource->id,
            'title' => $this->resource->title,
            'url' => $this->resource->url,
            'remarks' => $this->resource->remarks,
        ];
    }
}
