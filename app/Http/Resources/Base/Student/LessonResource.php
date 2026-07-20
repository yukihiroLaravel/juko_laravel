<?php

namespace App\Http\Resources\Base\Student;

use App\Model\Lesson;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonResource extends JsonResource
{
    /** @var Lesson */
    public $resource;

    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    #[\Override]
    public function toArray($request)
    {
        return [
            'lesson_id' => $this->resource->id,
            'url' => $this->resource->url,
            'title' => $this->resource->title,
            'remarks' => $this->resource->remarks,
            'order' => $this->resource->order,
        ];
    }
}
