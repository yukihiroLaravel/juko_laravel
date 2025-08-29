<?php

namespace App\Http\Resources\Instructor;

use App\Http\Resources\Base\Instructor\ChapterResource;
use App\Http\Resources\Base\Instructor\CourseDeadlineResource;
use App\Http\Resources\Base\Instructor\CourseResource;
use App\Http\Resources\Base\Instructor\LessonResource;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseShowResource extends JsonResource
{
    public function toArray($request): array
    {
        // まず deadline の配列を作る（なければ空配列）
        $deadlineArr = [];
        if ($this->resource->courseDeadline) {
            $deadlineArr = (new CourseDeadlineResource($this->resource->courseDeadline))
                ->toArray($request);
        }

        return [
            ...(new CourseResource($this->resource))->toArray($request),
            'chapters' => $this->resource->chapters->map(fn ($chapter) => [
                ...(new ChapterResource($chapter))->toArray($request),
                'lessons' => $chapter->lessons->map(
                    fn ($lesson) => (new LessonResource($lesson))->toArray($request)
                ),
            ]),

            // 中身があるときだけ 'deadline' キーを出す
            $this->mergeWhen(!empty($deadlineArr), [
                'deadline' => $deadlineArr,
            ]),
        ];
    }
}
