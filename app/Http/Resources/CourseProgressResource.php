<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CourseProgressResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $attendance = $this->resource['attendance'];
        $progressData = $this->resource['progressData'];

        return [
            'course' =>[
                'course_id' => $attendance->course_id,
                'progress' => $attendance->progress,
            ],
            "number_of_completed_chapters" => $progressData['completedChaptersCount'],
            "number_of_total_chapters" => $progressData['totalChaptersCount'],
            "number_of_completed_lessons" => $progressData['completedLessonsCount'],
            "number_of_total_lessons" => $progressData['totalLessonsCount'],
            "continue_lesson_id" => $progressData['youngestUnCompletedLessonId'],
        ];
    }
}
