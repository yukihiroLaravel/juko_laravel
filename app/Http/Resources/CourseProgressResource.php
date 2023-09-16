<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CourseProgressResource extends JsonResource
{
    private $progressDate;
    public function __construct($resource, $progressDate)
    {
        parent::__construct($resource);
        $this->progressDate = $progressDate;
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */    
    public function toArray($request)
    {
        return [
            'course' =>[
                'course_id' => $this->resource->course_id,
                'progress' => $this->resource->progress
            ],
            "number_of_completed_chapters" => $this->progressDate['getCompletedChaptersCount'],
            "number_of_total_chapters" => $this->progressDate['getTotalChaptersCount'],
            "number_of_completed_lessons" => $this->progressDate['getCompletedLessonsCount'],
            "number_of_total_lessons" => $this->progressDate['getTotalLessonsCount'],
            "continue_lesson_id" => $this->progressDate['getYoungestUnCompletedLessonId']
        ];        
    }
}
