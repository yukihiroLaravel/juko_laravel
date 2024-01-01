<?php

namespace App\Http\Resources\Manager;

use Illuminate\Http\Resources\Json\JsonResource;

class ChapterShowResource extends JsonResource
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
            'chapter_id' => $this->resource->id,
            'title' => $this->resource->title,
            'lessons' => $this->resource->lessons->sortBy('order')->map(function ($lesson) {
                return [
                    'lesson_id' => $lesson->id,
                    'title' => $lesson->title,
                    'url' => $lesson->url,
                    'remarks' => $lesson->remarks,
                    'order' => $lesson->order,    
                ];
            })
        ];
    }
}
