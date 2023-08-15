<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Model\Attendance;
use App\Model\Course;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $attendances = Attendance::with(['student', 'course'])
                                    ->where('course_id', $request->course_id)
                                    ->get();

        $course = Course::find($request->course_id);

        foreach ($attendances as $attendance) {
            $students[] = [
                'id' => $attendance->student->id,
                'nick_name' => $attendance->student->nick_name,
                'email' => $attendance->student->email,
                'course_title' => $attendance->course->title,
                'attendanced_at' => $attendance->created_at->format('Y/m/d'),
            ];
        }

        return response()->json([
            'data' => [
                'course' => [
                    'id' => $course->id,
                    'image' => $course->image,
                    'title' => $course->title,
                ],
                'students' => $students,
            ],
        ]);
    }
}
