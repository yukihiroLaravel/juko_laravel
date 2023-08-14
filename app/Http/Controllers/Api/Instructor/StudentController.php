<?php

namespace App\Http\Controllers\Api\Instructor;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Student;

class StudentController extends Controller
{
    public function show($student_id)
    {
        $student = Student::with('courses.chapters.lessons.lessonAttendance')->findOrFail($student_id);

        if (!$student) {
            return response()->json([
                "result" => "false",
                "error_message" => "Invalid Request Body.",
                "error_code" => 400
            ]); 
        }

        return response()->json([
            'data' => [
                'student' => [
                    'given_name_by_instructor' => $student->given_name_by_instructor,
                    'id' => $student->id,
                    'nick_name' => $student->nick_name,
                    'last_name' => $student->last_name,
                    'first_name' => $student->first_name,
                    'occupation' => $student->occupation,
                    'email' => $student->email,
                    'purpose' => $student->purpose,
                    'birth_date' => $student->birth_date,
                    'sex' => $student->sex,
                    'address' => $student->address,
                    'created_at' => $student->created_at,
                    'last_login_at' => $student->last_login_at,
                    'courses' => $student->courses->map(function ($course) {
                        return [
                            'id' => $course->id,
                            'image' => $course->image,
                            'title' => $course->title,
                            'progress' => $course->progress,
                            'chapters' => $course->chapters->map(function ($chapter) {
                                return [
                                    'chapter_id' => $chapter->id,
                                    'title' => $chapter->title,
                                    'lessons' => $chapter->lessons->map(function ($lesson) {
                                        return [
                                            'lesson_id' => $lesson->id,
                                            'lesson_attendance' => $lesson->lessonAttendance->map(function ($attendance) {
                                                return [
                                                    'id' => $attendance->id,
                                                    'attendance_id' => $attendance->attendance_id,
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
            ],
        ]);
    }
}
