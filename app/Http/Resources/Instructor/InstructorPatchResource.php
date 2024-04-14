<?php

namespace App\Http\Resources\Instructor;

use Illuminate\Http\Resources\Json\JsonResource;

class InstructorPatchResource extends JsonResource
{
    /** @var \App\Model\Instructor */
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
            'instructor_id' => $this->resource->id,
            'nick_name' => $this->resource->nick_name,
            'last_name' => $this->resource->last_name,
            'first_name' => $this->resource->first_name,
            'email' => $this->resource->email,
            'profile_image' => $this->resource->profile_image,
        ];
    }
}
