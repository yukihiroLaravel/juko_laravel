<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StudentEditResource extends JsonResource
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
            'student_id' => $this->id,
            'nick_name' => $this->nick_name,
            'last_name' => $this->last_name,
            'first_name' => $this->first_name,
            'email' => $this->email,
            'occupation' => $this->occupation,
            'purpose' => $this->purpose,
            'birth_date' => $this->birth_date->format('Y/m/d'),
            'sex' => $this->sex,
            'address' => $this->address,
        ];
    }
}
