<?php

namespace App\Http\Resources\Student;

use App\Enums\Course\DeadlineTypeEnum;
use App\Http\Resources\Base\Student\AttendanceResource;
use App\Http\Resources\Base\Student\ChapterResource;
use App\Http\Resources\Base\Student\CourseResource;
use App\Http\Resources\Base\Student\InstructorResource;
use App\Http\Resources\Base\Student\LessonAttendanceResource;
use App\Http\Resources\Base\Student\LessonResource;
use App\Http\Resources\Base\Student\TagResource;
use App\Model\Attendance;
use App\Model\Lesson;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceShowResource extends JsonResource
{
    /** @var Attendance */
    public $resource;

    #[\Override]
    public function toArray($request): array
    {
        return [
            ...(new AttendanceResource($this->resource))->toArray($request),
            
            'course' => [
                ...(new CourseResource($this->resource->course))->toArray($request),
                'instructor' => new InstructorResource($this->resource->course->instructor),
                'tags'       => TagResource::collection($this->resource->course->tags),
                'deadline_type' => $this->resource->course->deadline_type ?? 'none',
                'chapters' => ChapterResource::collection(
                    $this->resource->course->publicChapters
                )->collection->map(fn (ChapterResource $chapterResource) => [
                    ...$chapterResource->toArray($request),
                    'lessons' => $chapterResource->resource->lessons->map(function (Lesson $lesson) use ($request) {
                        $lessonAttendance = $this->resource->lessonAttendances->firstWhere('lesson_id', $lesson->id);
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
