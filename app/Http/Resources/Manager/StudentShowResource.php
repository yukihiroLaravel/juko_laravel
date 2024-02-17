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
        $student = $this->resource; 
        
        return [
            'given_name_by_instructor' => $student->given_name_by_instructor,
            'student_id' => $student->id,
            'nick_name' => $student->nick_name,
            'last_name' => $student->last_name,
            'first_name' => $student->first_name,
            'occupation' => $student->occupation,
            'email' => $student->email,
            'purpose' => $student->purpose,
            'birth_date' => $student->birth_date->format('Y/m/d'),
            'sex' => $student->sex,
            'address' => $student->address,
            'created_at' => $student->created_at->format('Y/m/d'),
            'last_login_at' => $student->last_login_at->format('Y/m/d'),
        ];
    }
}