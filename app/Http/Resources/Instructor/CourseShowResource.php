<?php

namespace App\Http\Resources\Instructor;

use App\Http\Resources\Base\Instructor\ChapterResource;
use App\Http\Resources\Base\Instructor\CourseResource;
use App\Http\Resources\Base\Instructor\LessonResource;
use App\Model\Course;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseShowResource extends JsonResource
{
    /** @var Course */
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
            ...(new CourseResource($this->resource))->toArray($request),
            'chapters' => $this->resource->chapters->map(fn ($chapter) => [
                ...(new ChapterResource($chapter))->toArray($request),
                'lessons' => $chapter->lessons->map(fn ($lesson) => (new LessonResource($lesson))->toArray($request)),
            ]),
            ...$this->formatDeadline(),
        ];
    }

    /**
     * deadline 整形（行なし/両方NULLなら空配列）
     */
    private function formatDeadline(): array
    {
        $dl = $this->resource->deadline; // ← ここがポイント（$this->deadline はNG）

        // ① 固定日
        if ($dl && $dl->fixed_date) {
            $fixed = $dl->fixed_date instanceof \Carbon\CarbonInterface
                ? $dl->fixed_date->toDateString()
                : (string) $dl->fixed_date;

            return [
                'deadline' => [
                    'mode'       => 'fixed_date',
                    'fixed_date' => $fixed,
                ],
            ];
        }

        // ② 相対日数
        if ($dl && $dl->relative_days !== null) {
            return [
                'deadline' => [
                    'mode'          => 'relative',
                    'relative_days' => (int) $dl->relative_days,
                ],
            ];
        }

        // ③ 期限なし
        return [];
    }
}
