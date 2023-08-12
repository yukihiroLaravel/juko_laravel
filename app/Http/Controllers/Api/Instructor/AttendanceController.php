<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Model\Attendance;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function store(Request $request)
    {
        $validateData = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'student_id' => 'required|exists:students,id',
        ]);

        $existingAttendance = Attendance::where('course_id', $validateData['course_id'])
            ->where('student_id', $validateData['student_id'])
            ->first();

        if ($existingAttendance) {
            return response()->json([
                'error' => '既に登録されています'], 409);
        }

        $attendance = new Attendance();
        $attendance->course_id = $validateData['course_id'];
        $attendance->student_id = $validateData['student_id'];
        $attendance->save();

        return response()->json(['message' => '登録しました'], 201);
    }
}
