<?php

namespace App\Http\Resources\Instructor;

use Illuminate\Http\Resources\Json\JsonResource;

class CourseShowResource extends JsonResource
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
            'course_id' => $this->resource->id,
            'title' => $this->resource->title,
            'image' => $this->resource->image,
            'status' => $this->resource->status,
            'chapters'=> $this->resource->chapters->map(function ($chapter) {
                return [
                    'chapter_id' => $chapter->id,
                    'title' => $chapter->title,
                    'status' => $chapter->status,
                    'lessons' => $chapter->lessons->map(function ($lesson) {
                        return [
                           'lesson_id'=>$lesson->id,
                           'url'=>$lesson->url,
                           'title'=>$lesson->title,
                           'remarks'=>$lesson->remarks,
                           'status' =>$lesson->status,
                        ];
                    })
                ];
            })
        ];
    }


}
