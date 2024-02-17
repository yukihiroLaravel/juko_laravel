<?php

namespace App\Http\Resources\Instructor;

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
        /** @var Collection<Chapter>  */
        $chapters = $this->resource['chapters'];

        return [
            'chapters' => $chapters->map(function (Chapter $chapter) {
                return [
                    'chapter_id' => $chapter->id,
                    'title' => $chapter->title,
                    'completed_count' => $chapter->completed_count,
                ];
            }),
            'students_count' => $this->resource['studentsCount'],
        ];
    }
}
