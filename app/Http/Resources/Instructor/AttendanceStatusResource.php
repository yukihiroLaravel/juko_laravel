<?php

namespace App\Http\Resources\Instructor;

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
            'progress' => $this->resource->progress,
            'attendance_id' => $this->resource->id,
            'course_id' => $this->resource->course->id,
            'course' => [
                'status' => $this->resource->course->status,
                'image' => $this->resource->course->image,
                'chapters' => $this->mapChapters($this->resource->course->chapters),
                'title' => $this->resource->course->title,
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
