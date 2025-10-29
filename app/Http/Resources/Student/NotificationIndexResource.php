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
        return $notifications->map(function (Notification $notification) {
            $deadline = optional($notification->course->courseDeadline);

            return [
                'notification_id' => $notification->id,
                'course_id' => $notification->course_id,
                'instructor_id' => $notification->instructor_id,
                'course_title' => $notification->course->title,
                'title' => $notification->title,
                'type' => $notification->type,
                'content' => $notification->content,
                'start_date' => $notification->start_date,
                'end_date' => $notification->end_date,

                // 期限情報（null か、オブジェクト）
                'deadline' => $deadline ? [
                    'fixed_date' => optional($deadline->fixed_date)->toDateString(),  // Y-m-d 等（cast/formatは必要に応じて）
                    'relative_days' => $deadline->relative_days,  // int|null
                ] : null,
            ];
        })->toArray();
    }
}
