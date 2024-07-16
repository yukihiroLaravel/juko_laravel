<?php

namespace App\Http\Resources\Manager;

use App\Model\Chapter;
use App\Model\Attendance;
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
    public function toArray($request)
    {
        return [
            'data' => [
                'attendance_id' => $this->resource->id,
                'progress' => $this->resource->progress,
                'course' => [
                    'course_id' => $this->resource->course->id,
                    'title' => $this->resource->course->title,
                    'status' => $this->resource->course->status,
                    'image' => $this->resource->course->image,
                    'chapters' => $this->mapChapters($this->resource->course->chapters),
                ],
            ],
        ];
    }

    /**
     * Calculate chapter progress
     *
     * @param Collection $chapters
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
