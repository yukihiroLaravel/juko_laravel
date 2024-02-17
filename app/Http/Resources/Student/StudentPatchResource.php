<?php

namespace App\Http\Resources\Student;

use Illuminate\Http\Resources\Json\JsonResource;

class StudentPatchResource extends JsonResource
{
    /** @var \App\Model\Student */
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
            'nick_name' => $this->resource->nick_name,
            'last_name' => $this->resource->last_name,
            'first_name' => $this->resource->first_name,
            'occupation' => $this->resource->occupation,
            'email' => $this->resource->email,
            'purpose' => $this->resource->purpose,
            'birth_date' => $this->resource->birth_date,
            'sex' => $this->resource->sex,
            'address' => $this->resource->address,
            'profile_image' => $this->resource->profile_image,
        ];
    }
}
