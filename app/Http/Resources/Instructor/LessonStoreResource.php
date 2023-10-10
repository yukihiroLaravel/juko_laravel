<?php

namespace App\Http\Resources\Instructor;

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
            'chapter_id' => $this->resource->chapter_id,
            'lesson' => [
                'lesson_id' => $this->resource->id,
                'title' => $this->resource->title,
                'order' => $this->order,
            ],
        ];
    }
}
