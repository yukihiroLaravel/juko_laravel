<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LessonAttendancePatchRequest;
use App\Model\LessonAttendance;
use Illuminate\Support\Facades\Log;

class LessonAttendanceController extends Controller
{
    public function update(LessonAttendancePatchRequest $request)
    {
        try {
            $lessonAttendance =  LessonAttendance::with('attendance')
                ->where('id', '=', $request->lesson_attendance_id)
                ->first();
            if ($lessonAttendance === null) {
                return response()->json([
                    "result" => false,
                    "error_code" => 404,
                    "error_message" => "Not found lesson attendance status."
                ]);
            }
            if ($request->user()->id !== $lessonAttendance->attendance->student_id) {
                return response()->json([
                    "result" => false,
                    "error_code" => 403,
                    "error_message" => "Forbidden."
                ]);
            }

            $lessonAttendance->update([
                'status' => $request->status,
            ]);

            return response()->json([
                "result" => true,
            ]);
        } catch (\RuntimeException $e) {
            Log::error($e->getMessage());
            return response()->json([
                "result" => false,
            ]);
        }
    }
}
