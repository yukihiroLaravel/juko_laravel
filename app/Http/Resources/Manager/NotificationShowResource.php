<?php

namespace App\Http\Resources\Manager;

use App\Http\Resources\Base\Instructor\InstructorResource;
use App\Http\Resources\Base\Instructor\NotificationResource;
use App\Model\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationShowResource extends JsonResource
{
    /** @var Notification */
    public $resource;

    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    #[\Override]
    public function toArray($request)
    {
        return [
            ...(new NotificationResource($this->resource))->toArray($request),
            'instructor' => new InstructorResource($this->resource->instructor),
        ];
    }
}
