<?php

namespace App\Http\Resources\Manager;

use App\Http\Resources\Base\Instructor\TagResource;
use App\Model\Chapter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\JsonResource;

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

        return [
            'chapters' => $chapters->map(fn (Chapter $chapter) => [
                'chapter_id' => $chapter->id,
                'title' => $chapter->title,
                'completed_count' => $chapter->completed_count,
                'tags' => TagResource::collection($chapter->course->tags),
            ]),
            'students_count' => $this->resource['studentsCount'],
            'attendanceDeadline' => $this['attendanceDeadline']
                ? $this['attendanceDeadline']->format('Y-m-d')
                : null,
        ];
    }
}
