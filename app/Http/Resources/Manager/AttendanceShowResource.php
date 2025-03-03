<?php

namespace App\Http\Resources\Manager;

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
                    'tags' => $chapter->course->tags->map(function ($tag) {
                        return [
                            'id' => $tag->id,
                            'instructor_id' => $tag->instructor_id,
                            'content' => $tag->content,
                        ];
                    }) ?? [],
                ];
            }),
            'students_count' => $this->resource['studentsCount'],
        ];
    }
}
