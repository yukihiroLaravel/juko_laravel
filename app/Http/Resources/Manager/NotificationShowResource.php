<?php

namespace App\Http\Resources\Manager;

use App\Http\Resources\Instructor\InstructorResource;
use App\Http\Resources\Notification\NotificationResource;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationShowResource extends JsonResource
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
            new NotificationResource($this->resource),
            'instructor' => new InstructorResource($this->resource->instructor),
        ];
    }
}
