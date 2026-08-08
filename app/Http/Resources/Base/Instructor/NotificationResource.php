<?php

namespace App\Http\Resources\Base\Instructor;

use App\Model\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /** @var Notification */
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
            'notification_id' => $this->resource->id,
            'course_id' => $this->resource->course_id,
            'course_title' => $this->resource->course->title,
            'title' => $this->resource->title,
            'content' => $this->resource->content,
            'start_date' => $this->resource->start_date,
            'end_date' => $this->resource->end_date,
            'type' => $this->resource->type,
            'status' => $this->resource->status,
        ];
    }
}
