<?php

namespace App\Http\Resources\Instructor;

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
            'course' => [
                'course_id' => $this->resource->course->id,
                'status' => $this->resource->course->status,
                'image' => $this->resource->course->image,
                'chapters' => $this->mapChapters($this->resource->course->chapters),
                'title' => $this->resource->course->title,
                'tags' => TagResource::collection($this->resource->course->tags),
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
