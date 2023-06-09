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
            'data' => [
                'chapter_id' => $this->chapter_id,
                'title' => $this->title,
                'lessons' => $this->lessons->map(function ($lesson) {
                    return [
                        'lesson_id' => $lesson->lesson_id,
                        'title' => $lesson->title,
                        'url' => $lesson->url,
                        'remark' => $lesson->remarks,
                        'order' => $lesson->order,
                    ];
                }),
            ],
        ];
    }
}
