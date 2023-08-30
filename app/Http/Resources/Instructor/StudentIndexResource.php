<?php

namespace App\Http\Resources\Instructor;

use Illuminate\Http\Resources\Json\JsonResource;

class StudentIndexResource extends JsonResource
{
    public function toArray($request)
    {
        $course = $this->resource[0];
        $attendances = $this->resource[1];

        return [
            'data' => [
                'course' => [
                    'id' => $course->id,
                    'image' => $course->image,
                    'title' => $course->title,
                ],
                'pagination' => [
                    'page' => $attendances->currentPage(),
                    'total' => $attendances->total(),
                ],
                'students' => $this->mapStudents($attendances),
            ],
        ];
    }

    protected function mapStudents($attendances)
    {
        return $attendances->map(function ($attendance) {
            return [
                'id' => $attendance->student->id,
                'nick_name' => $attendance->student->nick_name,
                'email' => $attendance->student->email,
                'course_title' => $attendance->course->title,
                'attendanced_at' => $attendance->created_at->format('Y/m/d'),
            ];
        });
    }
}