<?php

namespace App\Http\Resources\Manager;

use App\Model\Chapter;
use Illuminate\Database\Eloquent\Collection;
use App\Http\Resources\Tag\TagResource;
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
        /** @var Collection<int, Chapter> */
        $chapters = $this->resource['chapters'];

        return [
            'chapters' => $chapters->map(function (Chapter $chapter) {
                return [
                    'chapter_id' => $chapter->id,
                    'title' => $chapter->title,
                    'completed_count' => $chapter->completed_count,
                    'tags' => TagResource::collection($chapter->course->tags),
                ];
            }),
            'students_count' => $this->resource['studentsCount'],
        ];
    }
}
