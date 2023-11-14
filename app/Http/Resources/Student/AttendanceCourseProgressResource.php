<?php

namespace App\Http\Resources\Student;

use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceCourseProgressResource extends JsonResource
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
            'attendance' => [
                'attendance_id' => $attendance->id,
                'progress' => $attendance->progress,
                'course' => [
                    'course_id' => $attendance->course->id,
                    'title' => $attendance->course->title,
                    'image' => $attendance->course->image,
                ]
            ],
            "number_of_completed_chapters" => $progressData['completedChaptersCount'],
            "number_of_total_chapters" => $progressData['totalChaptersCount'],
            "number_of_completed_lessons" => $progressData['completedLessonsCount'],
            "number_of_total_lessons" => $progressData['totalLessonsCount'],
            "continue_lesson" => $progressData['youngestUnCompletedLessonId'],
        ];
    }
}
