<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\LessonAttendance;

class LessonAttendanceController extends Controller
{
    public function update(Request $request)
    {
        //　ToDo　ログインユーザーとレッスン受講状態の受講者が一致するか検証が必要
        LessonAttendance::where('id','=',$request->lesson_attendance_id)
        ->update([
            'status' => $request->status,
        ]);

        return response()->json([
            "result" => true,
        ]);
    }


}
