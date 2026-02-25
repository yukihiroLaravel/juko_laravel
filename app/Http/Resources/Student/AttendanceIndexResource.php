<?php

namespace App\Http\Resources\Student;

use App\Http\Resources\Base\Student\AttendanceResource;

use App\Http\Resources\Base\Student\CourseResource;
use App\Http\Resources\Base\Student\InstructorResource;
use App\Http\Resources\Base\Student\TagResource;
use App\Model\LessonAttendance;
use App\Model\Attendance;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceIndexResource extends JsonResource
{
    /** @var Attendance */
    public $resource;

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array $array
     */
    #[\Override]
    public function toArray($request): array
    {
        return [
            ...(new AttendanceResource($this->resource))->toArray($request),
            'course' => [
                ...(new CourseResource($this->resource->course))->toArray($request),
                //'continue_from' => $this->getYoungestUnCompletedLesson($this->resource),
                'continue_from' => AttendanceCourseProgressResource::collection($this->resource),
                'tags' => TagResource::collection($this->resource->course->tags),
                'instructor' => new InstructorResource($this->resource->course->instructor),
            ],
        ];
    }

     /**
     * 続きのレッスンIDと、それを含むチャプターのIDを取得する
     *
     * @param  Attendance  $attendance
     * @return array | null
     */
    private function getYoungestUnCompletedLesson($attendance)
    {
        // IDが最も若い未完了のチャプターの内、IDが最も若い未完了のレッスン
        $youngestUnCompletedLesson = [
            'chapter_id' => 1,
            'lesson_id' => 1,
        ];
        $attendance->course->chapters->each(function ($chapter) use ($attendance, &$youngestUnCompletedLesson) {
            if ($youngestUnCompletedLesson['lesson_id'] !== null) {
                return;
            }

            $chapter->lessons->each(function ($lesson) use ($attendance, &$youngestUnCompletedLesson, $chapter) {
                $lessonAttendance = $attendance->lessonAttendances->where('lesson_id', $lesson->id)->first();
                if ($lessonAttendance->status !== LessonAttendance::STATUS_COMPLETED_ATTENDANCE) {
                    if ($youngestUnCompletedLesson['lesson_id'] === null) {
                        $youngestUnCompletedLesson = [
                            'chapter_id' => $chapter->id,
                            'lesson_id' => $lesson->id,
                        ];

                        return;
                    }
                }
            });
        });
        if ($youngestUnCompletedLesson['lesson_id'] === null) {
            return null;
        }

        return $youngestUnCompletedLesson;
    }
}
