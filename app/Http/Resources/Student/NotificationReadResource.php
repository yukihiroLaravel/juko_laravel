<?php

namespace App\Http\Resources\Student;

use App\Model\Notification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationReadResource extends JsonResource
{
    /** @var Collection<int, Notification> */
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
        return $this->resource->map(fn (Notification $notification, $key) => [
            'notification_id' => $notification->id,
            'course_id' => $notification->course_id,
            'course_title' => $notification->course->title,
            'title' => $notification->title,
            'content' => $notification->content,
            'type' => $notification->type,
        ])
            ->toArray();
    }
}
