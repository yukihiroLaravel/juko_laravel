<?php

namespace App\Http\Resources;

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
        return $this->map(function ($notification) {    
            return [
                'id' => $notification->id,
                'course_id' => $notification->course_id,
                'course_title' => $notification->course->title,
                'type' => $notification->type,
                'title' => $notification->title,
                'content' => $notification->content,
            ];
        });
    }
}
