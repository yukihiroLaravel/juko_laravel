<?php

namespace App\Http\Resources\Instructor;

use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceStatusResource extends JsonResource
{
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
     * @param  \Illuminate\Database\Eloquent\Collection  $chapters
     * @return array
     */
    private function mapChapters($chapters)
    {
        return $chapters->map(function ($chapter) {
            $chapterProgress = $chapter->calculateChapterProgress($this->resource);
            return [
                'chapter_id' => $chapter->id,
                'title' => $chapter->title,
                'status' => $chapter->status,
                'progress' => $chapterProgress,
            ];
        });
    }
}
