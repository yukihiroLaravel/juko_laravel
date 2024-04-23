<?php

namespace App\Http\Resources\Student;

use App\Model\Notification;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationIndexResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $notifications = $this->resource;
        return [
            'notifications' => $this->mapNotifications($notifications),
            'pagination' => [
                'page' => $notifications->currentPage(),
                'total' => $notifications->total(),
            ],
        ];
    }

    /**
     * @param Collection<\App\Model\Notification> $notifications
     * @return array
     */
    private function mapNotifications($notifications)
    {
        return $notifications->map(function ($notification) {
            return [
                'notification_id' => $notification->id,
                'course_id' => $notification->course_id,
                'instructor_id' => $notification->instructor_id,
                'course_title' => $notification->course->title,
                'title' => $notification->title,
                'content' => $notification->content,
                'start_date' => $notification->start_date,
                'end_date' => $notification->end_date,
            ];
        })
            ->toArray();
    }

}
