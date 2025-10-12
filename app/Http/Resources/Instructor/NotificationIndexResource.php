<?php

namespace App\Http\Resources\Instructor;

use App\Http\Resources\Base\Instructor\NotificationResource;
use App\Http\Resources\Base\Instructor\TagResource;
use App\Model\Notification;
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
            'notifications' => $notifications->map(fn (Notification $notification) => [
                ...(new NotificationResource($notification))->toArray($request),
                'expiration_date' => $notification->course?->getDeadlineForInstructor(),
                'tags' => TagResource::collection($notification->course->tags),
            ]),
            'pagination' => [
                'page' => $notifications->currentPage(),
                'total' => $notifications->total(),
            ],
        ];
    }
}
