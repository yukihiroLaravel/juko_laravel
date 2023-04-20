<?php

namespace App\Http\Resources\Instructor;

use Illuminate\Http\Resources\Json\JsonResource;

class CourseGetResponse extends JsonResource
{
    public function __construct($course)
    {
        $this->courseId = $course;
    }
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
          $course = $this->courseId;
          return [
              'course_id' => $course->id,
              'title' => $course->title,
              'image' => $course->image,
              'chapters'=> $course->chapters->map(function ($chapter) {
                  return [
                      'chapter_id' => $chapter->id,
                      'title' => $chapter->title,
                      'lessons' => $chapter->lessons->map(function ($lesson) {
                          return [
                            'lesson_id'=>$lesson->id,
                            'url'=>$lesson->url,
                            'title'=>$lesson->title,
                            'remarks'=>$lesson->remarks,
                          ];
                      })
                  ];
              })
          ];
    }
}