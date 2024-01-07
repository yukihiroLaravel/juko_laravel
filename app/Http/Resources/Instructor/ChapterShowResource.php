<?php

namespace App\Http\Resources\Instructor;

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
            'chapter_id' => $this->id,
            'title' => $this->title,
            'lessons' => $this->lessons->sortBy('order')->values()->map(function ($lesson) {
                return [
                    'lesson_id' => $lesson->id,
                    'title' => $lesson->title,
                    'url' => $lesson->url,
                    'remarks' => $lesson->remarks,
                    'status' => $lesson->status,
                    'order' => $lesson->order,
                ];
            }),
        ];
    }
}