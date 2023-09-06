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
        // ToDo 認証ユーザーを一時的にid=1とする。
        $authId = 1;
        try {
            //ログインユーザーとレッスン受講者idが一致するか検証
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
            if ($authId !== $lessonAttendance->attendance->student_id) {
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

    public function edit()
    {
        return response()->json([]);
    }
}
