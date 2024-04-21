<?php

namespace App\Http\Resources\Manager;

use App\Model\Notification;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\JsonResource;

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
     * @param Collection<Notification> $notifications
     * @return array
     */
    private function mapNotifications($notifications)
    {
        return $notifications->map(function (Notification $notification) {
            return [
                'notification_id' => $notification->id,
                'course_id' => $notification->course_id,
                'course_title' => $notification->course->title,
                'title' => $notification->title,
                'content' => $notification->content,
                'type' => $notification->type,
                'start_date' => $notification->start_date,
                'end_date' => $notification->end_date,
            ];
        })
            ->toArray();
    }
}
