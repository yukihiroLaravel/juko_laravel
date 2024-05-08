<?php

namespace App\Http\Resources\Student;

use App\Model\Notification;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Database\Eloquent\Collection;

class NotificationReadResource extends JsonResource
{
    /** @var Collection<Notification> */
    public $resource;

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->resource->map(function (Notification $notification) {
            return [
                'notification_id' => $notification->id,
                'course_id' => $notification->course_id,
                'course_title' => $notification->course->title,
                'title' => $notification->title,
                'content' => $notification->content,
                'type' => $notification->type,
            ];
        })
            ->toArray();
    }
}
