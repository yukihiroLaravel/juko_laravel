<?php

namespace App\Http\Resources\Student;

use App\Http\Resources\Base\Student\ChapterResource;
use App\Http\Resources\Base\Student\CourseResource;
use App\Http\Resources\Base\Student\InstructorResource;
use App\Http\Resources\Base\Student\LessonAttendanceResource;
use App\Http\Resources\Base\Student\LessonResource;
use App\Http\Resources\Base\Student\TagResource;
use App\Model\Attendance;
use App\Model\Lesson;
use App\Model\LessonAttendance;
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
    #[\Override]
    public function toArray($request)
    {
        return [
            'attendance_id' => $this->resource->id,
            'course' => [
                ...(new CourseResource($this->resource->course))->toArray($request),
                'instructor' => new InstructorResource($this->resource->course->instructor),
                'tags' => TagResource::collection($this->resource->course->tags),
                'chapters' => ChapterResource::collection($this->resource->course->publicChapters)->collection->map(fn (ChapterResource $chapterResource) => [
                    ...$chapterResource->toArray($request),
                    'lessons' => $chapterResource->resource->lessons->map(function (Lesson $lesson) use ($request) {
                        $lessonAttendance = $this->resource->lessonAttendances->filter(fn (LessonAttendance $lessonAttendance) => $lesson->id === $lessonAttendance->lesson_id)->first();
                        assert($lessonAttendance instanceof LessonAttendance);

                        return [
                            ...(new LessonResource($lesson))->toArray($request),
                            'lessonAttendance' => new LessonAttendanceResource($lessonAttendance),
                        ];
                    }),
                ]),
            ],
        ];
    }
}
