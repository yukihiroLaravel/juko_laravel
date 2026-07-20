<?php

namespace App\Http\Resources\Student;

use App\Model\Student;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentShowResource extends JsonResource
{
    /** @var Student */
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
            'student_id' => $this->resource->id,
            'nick_name' => $this->resource->nick_name,
            'last_name' => $this->resource->last_name,
            'first_name' => $this->resource->first_name,
            'email' => $this->resource->email,
            'occupation' => $this->resource->occupation,
            'purpose' => $this->resource->purpose,
            'birth_date' => $this->resource->birth_date->format('Y/m/d'),
            'gender' => $this->resource->gender?->value,
            'address' => $this->resource->address,
            'profile_image' => $this->resource->profile_image,
        ];
    }
}
