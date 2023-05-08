<?php

namespace App\Http\Resources\Instructor;

use Illuminate\Http\Resources\Json\JsonResource;

class ChapterStoreResponse extends JsonResource
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
            'course_id' => $this->course_id,
            'chapter' => [
                'chapter_id' => $this->id,
                'title' => $this->title,
            ]
        ];
    }
}
