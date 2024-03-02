<?php

namespace App\Http\Resources\Manager;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationUpdateResource extends JsonResource
{
    /** @var \App\Model\Notification */
    public $resource;

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'type'       => $this->resource->type,
            'start_date' => $this->resource->start_date,
            'end_date'   => $this->resource->end_date,
            'title'      => $this->resource->title,
            'content'    => $this->resource->content,
        ];
    }
}
