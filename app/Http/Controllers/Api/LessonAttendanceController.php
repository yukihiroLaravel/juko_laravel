<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\LessonAttendance;

class LessonAttendanceController extends Controller
{
    public function update(Request $request)
    {
    return response()->json([
        "lesson_attendance_id" => $request->lesson_attendance_id,
        "lesson_attendance_id" => LessonAttendance::find($request)
    ]);
    }
}
