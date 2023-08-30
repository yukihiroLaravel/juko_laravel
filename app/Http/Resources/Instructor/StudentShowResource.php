<?php

namespace App\Http\Resources\Instructor;

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
            'student' => [
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
                'courses' => $student->attendances->map(function ($attendance) {
                    return [
                        'course_id' => $attendance->course->id,
                        'image' => $attendance->course->image,
                        'title' => $attendance->course->title,
                        'progress' => $attendance->progress,
                        'chapters' => $attendance->course->chapters->map(function ($chapter) {
                            return [
                                'chapter_id' => $chapter->id,
                                'title' => $chapter->title,
                                'lessons' => $chapter->lessons->map(function ($lesson) {
                                    return [
                                        'lesson_id' => $lesson->id,
                                        'lesson_attendance' => $lesson->lessonAttendances->map(function ($attendance) {
                                            return [
                                                'lesson_attendance_id' => $attendance->id,
                                                'status' => $attendance->status,
                                            ];
                                        }),
                                    ];
                                }),
                            ];
                        }),
                    ];
                }),
            ],
        ];
    }
}