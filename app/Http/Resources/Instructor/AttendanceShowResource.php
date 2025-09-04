<?php

namespace App\Http\Resources\Instructor;

use App\Http\Resources\Base\Instructor\TagResource;
use App\Model\Chapter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\CarbonImmutable;

class AttendanceShowResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    #[\Override]
    public function toArray($request)
    {
        /** @var Collection<int, Chapter> */
        $chapters = $this->resource['chapters'];

        $courseDeadline = $this->resource['course_deadline'];

        return [
            'chapters' => $chapters->map(fn (Chapter $chapter) => [
                'chapter_id' => $chapter->id,
                'title' => $chapter->title,
                'completed_count' => $chapter->completed_count,
            ]),
            'students_count' => $this->resource['studentsCount'],
            'tags' => TagResource::collection($this->resource['tags']),
            'attendance_deadline' => $this->resource['course_deadline'] ? [
                'fixed_date' => $this->resource['course_deadline']['fixed_date']
                    ? \Carbon\Carbon::parse($this->resource['course_deadline']['fixed_date'])->format('Y-m-d')
                    : null,
                'relative_days' => $this->resource['course_deadline']['relative_days'],
            ] : null,
        ];
    }
}
