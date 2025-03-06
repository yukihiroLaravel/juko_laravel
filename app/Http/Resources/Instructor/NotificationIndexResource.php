<?php

namespace App\Http\Resources\Instructor;

use App\Http\Resources\Tag\TagResource;
use App\Http\Resources\Notification\NotificationResource;
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
            return [
                'notification' => new NotificationResource($notification),
                'tags' => TagResource::collection($notification->course->tags),
            ];
        })
            ->toArray();
    }
}
