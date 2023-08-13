<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Model\Attendance;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $attendances = Attendance::where('course_id', $request->course_id)->get();

        $students = [];
        
        foreach ($attendances as $attendance) {
            $student = $attendance->student;
            
            $students[] = [
                'id' => $student->id,
                'nick_name' => $student->nick_name,
                'email' => $student->email,
                'course_title' => $attendance->course->title,
                'attendanced_at' => $attendance->created_at->format('Y/m/d'),
            ];
        }

        return response()->json([
            'data' => [
                'course' => [
                    'id' => $attendances->first()->course->id,
                    'image' => $attendances->first()->course->image,
                    'title' => $attendances->first()->course->title,
                ],
                'students' => $students,
            ],
        ]);
    }
}
