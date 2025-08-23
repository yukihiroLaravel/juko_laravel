<?php

namespace App\Http\Resources\Instructor;

use App\Http\Resources\Base\Instructor\ChapterResource;
use App\Http\Resources\Base\Instructor\CourseResource;
use App\Http\Resources\Base\Instructor\LessonResource;
use App\Model\Course;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

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
        $mode = $this->resource->deadline_type ?? 'none';

        $row = DB::table('course_deadlines')
            ->where('course_id', $this->resource->id)
            ->first();

        $deadline = ['mode' => $mode];

        if ($mode === 'fixed_date') {
            $deadline['fixed_date'] = $row?->fixed_date;
        } elseif ($mode === 'relative') {
            $deadline['relative_value'] = $row?->relative_days;
            $deadline['relative_unit']  = 'days';
        }

        return [
            ...(new CourseResource($this->resource))->toArray($request),
            'chapters' => $this->resource->chapters->map(fn ($chapter) => [
                ...(new ChapterResource($chapter))->toArray($request),
                'lessons' => $chapter->lessons->map(fn ($lesson) => (new LessonResource($lesson))->toArray($request)),
            ]),
            'deadline' => $deadline,
        ];
    }
}
