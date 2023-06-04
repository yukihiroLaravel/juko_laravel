<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function delete($lesson_id)
    {
        // レッスンの削除処理を行う

        // レスポンス:正常
        $response = [
            "result" => true,
        ];

        // レスポンス:異常
        $errorResponse = [
            "result" => false,
            "error_message" => "Invalid Request Body.",
            "error_code" => "400"
        ];

        return response()->json([]);
    }
}
