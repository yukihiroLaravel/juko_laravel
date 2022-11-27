<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LessonGetResponse  extends JsonResource
{
    public function toArray($attendance)
    {
        return $this->resource->map(function($value, $key) {
            return [
                'chapter_id' => $value->chapter->id,
                'title' => $value->course->title,
                'attendance' => [
                    'attendance_id' => $value->id,
                    'progress' => $value->progress,
                ],
            ];
        });
    }
}

