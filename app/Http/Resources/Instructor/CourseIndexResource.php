<?php

namespace App\Http\Resources\Instructor;

use App\Http\Resources\Base\Instructor\TagResource;
use App\Model\Course;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseIndexResource extends JsonResource
{
    /** @var Course */
    public $resource;

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    #[\Override]
    public function toArray($request)
    {
        return [
            'course_id' => $this->resource->id,
            'image' => $this->resource->image,
            'title' => $this->resource->title,
            'status' => $this->resource->status,
            'has_active_students' => (bool) $this->resource->has_active_students,
            'tags' => TagResource::collection($this->resource->tags),
            
            // 期限があるときはキーを出す（期限が無いときはキーを出さない）
            'attendance_deadline' => $this->when(
                filled($this->resource->attendance_deadline),
                fn () => $this->resource->attendance_deadline->toDateString() // 時刻も必要なら toDateTimeString()
            ),
        ];
    }
}
