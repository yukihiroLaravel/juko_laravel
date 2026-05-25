<?php

namespace App\Http\Resources\Base\Student;

use App\Model\Notification;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /** @var Notification */
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
        /** @var Notification $notification */
        $notification = $this->resource['notification'];

        return [
            'notification_id' => $notification->id,
            'course_id' => $notification->course_id,
            'course_title' => $notification->course->title,
            'title' => $notification->title,
            'content' => $notification->content,
            'instructor_nick_name' => $notification->instructor?->nick_name,
            'start_date' => $notification->start_date,
            'end_date' => $notification->end_date,
            'attendance_deadline' => $this->resource['attendance_deadline'] ?? null,
        ];
    }
}
