<?php

namespace App\Http\Resources\Student;

use Illuminate\Http\Resources\Json\JsonResource;

class StudentPostResource extends JsonResource
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
            'nick_name'  => $this->nick_name,
            'last_name'  => $this->last_name,
            'first_name' => $this->first_name,
            'occupation' => $this->occupation,
            'email'      => $this->email,
            'purpose'    => $this->purpose,
            'birth_date' => $this->birth_date,
            'sex'        => $this->sex,
            'address'    => $this->address,
        ];
    }
}