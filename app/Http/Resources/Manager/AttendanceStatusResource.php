<?php

namespace App\Http\Resources\Manager;

use App\Http\Resources\Base\Instructor\CourseResource;
use App\Http\Resources\Base\Instructor\TagResource;
use App\Model\Attendance;
use App\Model\Chapter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceStatusResource extends JsonResource
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
            'tag' => TagResource::collection($this->resource->course->tags),
            'course' => [
                ...(new CourseResource($this->resource->course))->toArray($request),
                'chapters' => $this->mapChapters($this->resource->course->chapters),
                'attendance_deadline' => $this->resource->course->attendance_deadline !== null
                    ? $this->resource->course->attendance_deadline->format('Y-m-d')
                    : null,
            ],
        ];
    }

    /**
     * @param  Collection<int, Chapter>  $chapters
     * @return array
     */
    private function mapChapters(Collection $chapters)
    {
        return $chapters->map(function (Chapter $chapter) {
            $chapterProgress = $chapter->calculateChapterProgress($this->resource);

            return [
                'chapter_id' => $chapter->id,
                'title' => $chapter->title,
                'status' => $chapter->status,
                'progress' => $chapterProgress,
            ];
        })
            ->toArray();
    }
}
