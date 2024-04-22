<?php

namespace App\Http\Resources\Manager;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentShowResource extends JsonResource
{
    /** @var \App\Model\Student */
    public $resource;

    // 年齢を計算
    public function nenrei($age){
        // リクエストされた受講生を取得
        $student = Student::findOrFail($request->student_id);
        $birthDay = $student->attendances->pluck('birth_date');
        $toDay = Carbon::today();
        $nichi = $birthDay->diffInDays($toDay);
        $toshi = intval($nichi/365);
        $age = strval($toshi)->attendances->pluck('age');
        /* 年齢のカラム更新 */
    }

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
            'given_name_by_instructor' => $this->resource->given_name_by_instructor,
            'nick_name' => $this->resource->nick_name,
            'last_name' => $this->resource->last_name,
            'first_name' => $this->resource->first_name,
            'occupation' => $this->resource->occupation,
            'email' => $this->resource->email,
            'purpose' => $this->resource->purpose,
            'age' => $this->resource->age,
            'birth_date' => $this->resource->birth_date->format('Y/m/d'),
            'sex' => $this->resource->sex,
            'address' => $this->resource->address,
            'created_at' => $this->resource->created_at->format('Y/m/d'),
            'last_login_at' => $this->resource->last_login_at->format('Y/m/d'),
            'profile_image' => $this->resource->profile_image,
        ];
    }
}
