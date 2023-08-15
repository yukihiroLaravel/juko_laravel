<?php

namespace App\Http\Controllers\Api\Instructor;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Student;

class StudentController extends Controller
{
    public function show($student_id)
    {
        $student = Student::with(['attendances.course.chapters.lessons.lessonAttendance'])->findOrFail($student_id);

        return response()->json([
            'data' => [
                'student' => [
                    'given_name_by_instructor' => $student->given_name_by_instructor,
                    'student_id' => $student->id,
                    'nick_name' => $student->nick_name,
                    'last_name' => $student->last_name,
                    'first_name' => $student->first_name,
                    'occupation' => $student->occupation,
                    'email' => $student->email,
                    'purpose' => $student->purpose,
                    'birthdate' => $student->birthdate,
                    'sex' => $student->sex,
                    'address' => $student->address,
                    'created_at' => $student->created_at->format('Y/m/d'),
                    'last_login_at' => $student->last_login_at, //カラム追加後、日付はフォーマットして渡す ->format('Y/m/d')
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
                                            'lesson_attendance' => $lesson->lessonAttendance->map(function ($attendance) {
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
            ],
        ]);
    }
}
