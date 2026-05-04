<?php

namespace App\Http\Resources\Instructor\Attendance;

use App\Http\Resources\Base\Instructor\ChapterResource;
use App\Model\Chapter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property array{chapter: Chapter, completedStudentsCount: int} $resource
 */
class ChapterCompletedStudentsCountResource extends JsonResource
{
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            ...(new ChapterResource($this->resource['chapter']))->toArray($request),
            'completed_students_count' => $this->resource['completedStudentsCount'],
        ];
    }
}
