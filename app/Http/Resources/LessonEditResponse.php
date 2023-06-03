<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LessonEditResponse extends JsonResource
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
                'lesson' => [
                    [
                        'lesson_id' => $this->chapter_id,
                        'title' => $this->title,
                        'url' => $this->url,
                        'remark' => $this->remark,
                        'order' => $this->order,
                    ],
                ],
            ],
        ];
    }    
}

