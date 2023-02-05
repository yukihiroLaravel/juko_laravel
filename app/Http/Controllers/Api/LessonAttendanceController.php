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
            $lessonAttendance = LessonAttendance::where('id', '=', $request->lesson_attendance_id)->first();
            if ($authId !== $lessonAttendance->attendance->student_id) {
                return response()->json([
                    "result" => false,
                    "error_code" => 403
                ]);
            } else if ($authId === null) {
                return response()->json([
                    "result" => false,
                    "error_code" => 404
                ]);
            }
            return response()->json([
                "result" => true
            ]);

            $lessonAttendance->update([
                'status' => $request->status,
            ]);

            return response()->json([
                "result" => true,
                "student_id" => $lessonAttendance->attendance->student_id
            ]);
        } catch (\Exception $e) {
            $errorMessage = 'update_error.';
            Log::error($errorMessage);
            return response()->json([
                "result" => false,
                "error_log" => $errorMessage //←エラーログに残す※例外のメッセージは要らない。理由対処しきれない。メッセージを出したところでフロントでは処理しようがない。
            ]);
        }
    }
}
