<?php

namespace App\Http\Resources\Manager;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationShowResource extends JsonResource
{
    /** @var \App\Model\Notification */
    public $resource;

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'notification_id' => $this->resource->id,
            'course_id' => $this->resource->course_id,
            'course_title' => $this->resource->course->title,
            'title' => $this->resource->title,
            'content' => $this->resource->content,
            'start_date' => $this->resource->start_date,
            'end_date' => $this->resource->end_date,
            'type' => $this->resource->type,
        ];
    }
}
