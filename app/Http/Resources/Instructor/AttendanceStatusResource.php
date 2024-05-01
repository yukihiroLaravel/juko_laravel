<?php

namespace App\Http\Resources\Instructor;

use App\Model\Attendance;
use App\Model\LessonAttendance;
use App\Model\Chapter;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceStatusResource extends JsonResource
{
    /**
     * @var Attendance
     */
    protected $attendance;
    protected $chapter;

    public function __construct(Attendance $attendance)
    {
        $this->attendance = $attendance;
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
            'data' => [
                'attendance_id' => $this->attendance->id,
                'progress' => $this->attendance->progress,
                'course' => [
                    'course_id' => $this->attendance->course->id,
                    'title' => $this->attendance->course->title,
                    'status' => $this->attendance->course->status,
                    'image' => $this->attendance->course->image,
                    'chapters' => $this->calculateChapterProgress($this->attendance->course->chapters),
                ],
            ],
        ];
    }

    /**
     * Calculate chapter progress
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $chapters
     * @return array
     */
    private function calculateChapterProgress($chapters)
    {
        return $chapters->map(function ($chapter) {
            $chapterProgress = $chapter->calculateChapterProgress($this->attendance);
            return [
                'chapter_id' => $chapter->id,
                'title' => $chapter->title,
                'status' => $chapter->status,
                'progress' => $chapterProgress,
            ];
        });
    }
}