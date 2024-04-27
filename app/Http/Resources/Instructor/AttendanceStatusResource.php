<?php

namespace App\Http\Resources\Instructor;

use App\Model\LessonAttendance;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceStatusResource extends JsonResource
{
    protected $attendance;

    public function __construct($resource)
    {
        parent::__construct($resource);
        $this->attendance = $resource;
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
    if ($this->attendance !== null && is_object($this->attendance)) {
        return [
            'data' => [
                'attendance_id' => $this->attendance->id,
                'progress' => $this->attendance->progress,
                'course' => [
                    'course_id' => $this->attendance->course->id,
                    'title' => $this->attendance->course->title,
                    'status' => $this->attendance->course->status,
                    'image' => $this->attendance->course->image,
                    'chapters' => $this->attendance->course->chapters->map(function ($chapter) {
                        return [
                            'chapter_id' => $chapter->id,
                            'title' => $chapter->title,
                            'status' => $chapter->status,
                            'progress' => $this->calculateChapterProgress($chapter),
                        ];
                    }),
                ],
            ],
        ];
    } else {
        return [];
    }
    }

    private function calculateChapterProgress($chapter)
    {
        $completedCount = $this->calculateCompletedLessonCount($chapter);
        $totalLessonsCount = $chapter->lessons->count();
        return $totalLessonsCount > 0 ? ($completedCount / $totalLessonsCount) * 100 : 0;
    }

    private function calculateCompletedLessonCount($chapter)
    {
        return $chapter->lessons->filter(function ($lesson) {
            $lessonAttendance = $lesson->lessonAttendances->firstWhere('attendance_id', $this->attendance->id);
            return $lessonAttendance && $lessonAttendance->status === LessonAttendance::STATUS_COMPLETED_ATTENDANCE;
        })->count();
    }
}