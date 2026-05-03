<?php

namespace App\Http\Resources\Instructor\Attendance;

use App\Http\Resources\Base\Instructor\ChapterResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChapterCompletedStudentsCountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    #[\Override]
    public function toArray($request): array
    {
        return [
            ...(new ChapterResource($this->resource['chapter']))->toArray($request),
            'completed_students_count' => $this->resource['completedStudentsCount'],
        ];
    }
}
