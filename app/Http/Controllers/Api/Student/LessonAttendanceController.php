<?php

namespace App\Http\Controllers\Api\Student;

use RuntimeException;
use App\Model\LessonAttendance;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\LessonAttendancePatchRequest;

class LessonAttendanceController extends Controller
{
    /**
     * レッスン出席状況更新API
     *
     * @param LessonAttendancePatchRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(LessonAttendancePatchRequest $request)
    {
        try {
            /** @var LessonAttendance $lessonAttendance */
            $lessonAttendance =  LessonAttendance::with('attendance')
                ->find($request->lesson_attendance_id);

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
        } catch (RuntimeException $e) {
            Log::error($e->getMessage());
            return response()->json([
                "result" => false,
            ]);
        }
    }
}
