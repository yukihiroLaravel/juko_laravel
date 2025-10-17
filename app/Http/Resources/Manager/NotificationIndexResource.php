<?php

namespace App\Http\Resources\Manager;

use App\Http\Resources\Base\Instructor\CourseResource;
use App\Http\Resources\Base\Instructor\InstructorResource;
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
            'course_title' => $notification->course->title,
            'title' => $notification->title,
            'content' => $notification->content,
            'type' => $notification->type,
            'instructor' => new InstructorResource($notification->instructor),
            'start_date' => $notification->start_date,
            'end_date' => $notification->end_date,
            'status' => $notification->status,
            'course' => new CourseResource($notification->course),
        ])
            ->toArray();
    }
}
