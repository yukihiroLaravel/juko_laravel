<?php

namespace App\Http\Resources\Student;

use App\Model\Notification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;

class NotificationIndexResource extends JsonResource
{
    /** @var LengthAwarePaginator */
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
        $notifications = $this->resource;

        return [
            'notifications' => $this->mapNotifications($notifications->getCollection()),
            'pagination' => [
                'page' => $notifications->currentPage(),
                'total' => $notifications->total(),
            ],
        ];
    }

    /**
     * @param  Collection<int, Notification>  $notifications
     * @return array
     */
    private function mapNotifications($notifications)
    {
        return $notifications->map(fn (Notification $notification) => [
            'notification_id' => $notification->id,
            'course_id' => $notification->course_id,
            'instructor_id' => $notification->instructor_id,
            'course_title' => $notification->course->title,
            'title' => $notification->title,
            'type' => $notification->type,
            'content' => $notification->content,
            'instructor_nick_name' => $notification->instructor?->nick_name,
            'start_date' => $notification->start_date,
            'end_date' => $notification->end_date,
        ])
            ->toArray();
    }
}
