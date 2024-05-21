<?php

namespace App\Http\Resources\Instructor;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Model\Student;
use Carbon\CarbonImmutable;

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
            'student_id' => $this->id,
            'given_name_by_instructor' => $this->given_name_by_instructor,
            'nick_name' => $this->nick_name,
            'last_name' => $this->last_name,
            'first_name' => $this->first_name,
            'occupation' => $this->occupation,
            'email' => $this->email,
            'purpose' => $this->purpose,
            'birth_date' => $this->birth_date->format('Y/m/d'),
            'age' => $this->calcAge(new CarbonImmutable()),
            'gender' => $this->gender,
            'address' => $this->address,
            'created_at' => $this->created_at->format('Y/m/d'),
            'last_login_at' => $this->last_login_at->format('Y/m/d'),
            'profile_image' => $this->profile_image,
        ];
    }
}
