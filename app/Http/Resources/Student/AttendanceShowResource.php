<?php

namespace App\Http\Resources\Student;

use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Lesson;
use App\Model\LessonAttendance;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceShowResource extends JsonResource
{
    /** @var Attendance */
    public $resource;

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'attendance_id' => $this->resource->id,
            'progress' => $this->resource->progress,
            'course' => $this->course($this->resource->course),
        ];
    }

    private function course(Course $course)
    {
        return [
            'course_id' => $course->id,
            'title' => $course->title,
            'image' => $course->image,
            'instructor' => $this->instructor($course->instructor),
            'chapters' => $this->chapters($course->chapters),
        ];
    }

    private function instructor(Instructor $instructor)
    {
        return [
            'instructor_id' => $instructor->id,
            'nick_name' => $instructor->nick_name,
            'last_name' => $instructor->last_name,
            'first_name' => $instructor->first_name,
            'email' => $instructor->email,
            'profile_image' => $instructor->profile_image,
        ];
    }

    private function chapters(Collection $chapters)
    {
        return $chapters->map(function(Chapter $chapter) {
            return [
                'chapter_id' => $chapter->id,
                'title' => $chapter->title,
                'lessons' => $this->lessons($chapter->lessons),
            ];
        });
    }

    private function lessons(Collection $lessons)
    {
        return $lessons->map(function(Lesson $lesson) {
            /** @var LessonAttendance $lessonAttendance */
            $lessonAttendance = $this->resource->lessonAttendances->filter(function (LessonAttendance $lessonAttendance) use ($lesson) {
                return $lesson->id === $lessonAttendance->lesson_id;
            })->first();
            return [
                'lesson_id' => $lesson->id,
                'title' => $lesson->title,
                'url' => $lesson->url,
                'remarks' => $lesson->remarks,
                'lessonAttendance' => [
                    'lesson_attendance_id' => $lessonAttendance->id,
                    'status' => $lessonAttendance->status,
                ]
            ];
        });
    }
}
