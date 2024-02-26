<?php

namespace App\Http\Resources\Manager;

use Illuminate\Http\Resources\Json\JsonResource;

class StudentShowResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'given_name_by_instructor' => $this->resource->given_name_by_instructor,
            'student_id' => $this->resource->id,
            'nick_name' => $this->resource->nick_name,
            'last_name' => $this->resource->last_name,
            'first_name' => $this->resource->first_name,
            'occupation' => $this->resource->occupation,
            'email' => $this->resource->email,
            'purpose' => $this->resource->purpose,
            'birth_date' => $this->resource->birth_date->format('Y/m/d'),
            'sex' => $this->resource->sex,
            'address' => $this->resource->address,
            'created_at' => $this->resource->created_at->format('Y/m/d'),
            'last_login_at' => $this->resource->last_login_at->format('Y/m/d'),
        ];
    }
}
