<?php

namespace App\Http\Resources\Student;

use App\Enums\Course\DeadlineTypeEnum;
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
        $deadlineDate = $this->resource->calcDeadlineForStudent();
        $setting = optional($this->resource->course->deadline);

        $type = $setting?->fixed_date
            ? DeadlineTypeEnum::FIXED_DATE->value
            : ($setting?->relative_days
                ? DeadlineTypeEnum::RELATIVE_DAYS->value
                : DeadlineTypeEnum::NONE->value);

        return [
            'attendance_id' => $this->resource->id,
            'deadline_date' => $deadlineDate?->format('Y-m-d'),
            'expired' => $this->resource->isExpired(),

            'course' => [
                ...(new CourseResource($this->resource->course))->toArray($request),
                'instructor' => new InstructorResource($this->resource->course->instructor),
                'tags' => TagResource::collection($this->resource->course->tags),

                'deadline' => [
                    'type' => $type,
                    'fixed_date' => $setting?->fixed_date?->format('Y-m-d'),
                    'relative_days' => $setting?->relative_days,
                ],

                'chapters' => ChapterResource::collection(
                    $this->resource->course->publicChapters
                )->collection->map(fn (ChapterResource $chapterResource) => [
                    ...$chapterResource->toArray($request),
                    'lessons' => $chapterResource->resource->lessons->map(function (Lesson $lesson) {
                        $la = $this->resource->lessonAttendances->firstWhere('lesson_id', $lesson->id);

                        return [
                            ...(new LessonResource($lesson))->toArray(request()),
                            'lessonAttendance' => new LessonAttendanceResource($la),
                        ];
                    }),
                ]),
            ],
        ];
    }
}
