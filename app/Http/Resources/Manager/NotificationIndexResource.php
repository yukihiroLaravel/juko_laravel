<?php

namespace App\Http\Resources\Manager;

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

    private function mapNotifications($notifications)
    {
        return $notifications->map(function ($notification) {
            return [
                'id' => $notification->id,
                'course_id' => $notification->course_id,
                'course_title' => $notification->course->title,
                'title' => $notification->title,
                'type' => $notification->type,
                'start_date' => $notification->start_date,
            ];
        });
    }
}
