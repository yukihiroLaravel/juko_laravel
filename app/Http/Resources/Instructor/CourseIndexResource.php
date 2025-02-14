<?php

namespace App\Http\Resources\Instructor;

use Illuminate\Http\Resources\Json\ResourceCollection;

class CourseIndexResource extends ResourceCollection
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
            'data' => $this->collection->map(function ($course) {
                return [
                    'course_id' => $course->id,
                    'image' => $course->image,
                    'title' => $course->title,
                    'status' => $course->status,
                ];
            }),
            'pagination' => [
                'current_page' => $this->currentPage(),
                'last_page' => $this->lastPage(),
                'per_page' => $this->perPage(),
                'total' => $this->total(),
                'next_page_url' => $this->nextPageUrl(),
                'prev_page_url' => $this->previousPageUrl(),
            ],
        ];
    }
}
