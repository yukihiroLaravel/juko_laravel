<?php

namespace App\Http\Resources\Manager;

use App\Model\Instructor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstructorShowResource extends JsonResource
{
    /** @var Instructor */
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
            'instructor_id' => $this->resource->id,
            'nick_name' => $this->resource->nick_name,
            'last_name' => $this->resource->last_name,
            'first_name' => $this->resource->first_name,
            'email' => $this->resource->email,
            'profile_image' => $this->resource->profile_image,
            'instructor_type' => $this->resource->type,
        ];
    }
}
