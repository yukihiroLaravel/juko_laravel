<?php

namespace App\Http\Resources\Student;

use Illuminate\Http\Resources\Json\JsonResource;

class StudentShowResource extends JsonResource
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
            'student_id' => $this->resource->id,
            'nick_name' => $this->resource->nick_name,
            'last_name' => $this->resource->last_name,
            'first_name' => $this->resource->first_name,
            'email' => $this->resource->email,
            'occupation' => $this->resource->occupation,
            'purpose' => $this->resource->purpose,
            'birth_date' => $this->resource->birth_date->format('Y/m/d'),
            'sex' => $this->resource->sex,
            'address' => $this->resource->address,
            'profile_image' => $this->resource->profile_image,
        ];
    }
}
