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
        $lessons = [];
        foreach ($this->lessons as $lesson){
            $lessons[] = [
                'lesson_id' => $lesson->id,
                'title' => $lesson->title,
                'url' => $lesson->url,
                'remark' => $lesson->remarks,
                'order' => $lesson->order,
            ];
        }

        return [
            'data' => [
                'chapter_id' => $this->id,
                'title' => $this->title,
                'lessons' => $lessons,
            ],
        ];
    }
    

}
