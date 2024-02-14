<?php

namespace App\Http\Resources\Manager;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationShowResource extends JsonResource
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
            'course_id' => $this->resource->course_id,
            'title' => $this->resource->title,
            'type' => $this->resource->type,
            'start_date' => $this->resource->start_date,
            'end_date' => $this->resource->end_date,
            'content' => $this->resource->content
        ];
    }
}
