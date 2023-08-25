<?php

namespace App\Http\Resources\Instructor;

use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceShowResource extends JsonResource
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
            'chapters' => $this->resource['chapters']->map(function ($chapter) {
                return [
                    'chapter_id' => $chapter->id,
                    'title' => $chapter->title,
                    'completed_count' => $chapter->completedCount,
                ];
            }),
            'students_count' => $this->resource['studentsCount'],
        ];
    }
}
