<?php

namespace App\Http\Resources\Instructor;

use App\Http\Resources\Notification\NotificationResource;
use App\Http\Resources\Tag\TagResource;
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
            'notifications' => $notifications->map(function (Notification $notification) use ($request) {
                return [
                    ...(new NotificationResource($notification))->toArray($request),
                    'tags' => TagResource::collection($notification->course->tags),
                ];
            }),
            'pagination' => [
                'page' => $notifications->currentPage(),
                'total' => $notifications->total(),
            ],
        ];
    }
}
