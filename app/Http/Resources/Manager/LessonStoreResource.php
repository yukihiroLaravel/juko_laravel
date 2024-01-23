<?php

namespace App\Http\Resources\Manager;

use Illuminate\Http\Resources\Json\JsonResource;

class LessonStoreResource extends JsonResource
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
            'id' => $this->id,
            'chapter_id' => $this->resource->chapter_id,
            'title' => $this->resource->title,
            'status' => $this->resource->status,
            'order' => $this->resource->order,
        ];
    }
}
