<?php

namespace App\Http\Resources\Manager;

use App\Model\Student;
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
        $student = $this->resource['student'];
        $age = $this->resource['ageData'];
        return [
            'student_id' => $student->id,
            'given_name_by_instructor' => $student->given_name_by_instructor,
            'nick_name' => $student->nick_name,
            'last_name' => $student->last_name,
            'first_name' => $student->first_name,
            'occupation' => $student->occupation,
            'email' => $student->email,
            'purpose' => $student->purpose,
            'age' => $age,
            'birth_date' => $student->birth_date->format('Y/m/d'),
            'sex' => $student->sex,
            'address' => $student->address,
            'created_at' => $student->created_at->format('Y/m/d'),
            'last_login_at' => $student->last_login_at->format('Y/m/d'),
            'profile_image' => $student->profile_image,
        ];
    } 
}
