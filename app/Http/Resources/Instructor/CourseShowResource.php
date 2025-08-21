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
        // ① まず Base の CourseResource を配列化
        $base = (new CourseResource($this->resource))->toArray($request);

        // ② 期限が無いときはキーごと消す／あるときは表示形式を揃えて上書き
        if (blank($this->resource->attendance_deadline)) {
            unset($base['attendance_deadline']); // ← null を消す
        } else {
            $base['attendance_deadline'] = $this->resource->attendance_deadline->toDateString();
            // 時刻まで要るなら ->toDateTimeString()
        }

        // ③ 返却（元の構造を崩さずスプレッドで展開）
        return [
            ...$base,
            'chapters' => $this->resource->chapters->map(
                fn ($chapter) => [
                    ...(new ChapterResource($chapter))->toArray($request),
                    'lessons' => $chapter->lessons->map(
                        fn ($lesson) => (new LessonResource($lesson))->toArray($request)
                    ),
                ]
            ),
        ];
    }
}
