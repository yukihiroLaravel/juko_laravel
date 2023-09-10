<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Model\Notification; // Notificationモデルをインポート
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function show(Request $request, $notification_id)
    {
        // 通知情報をデータベースから取得
        $notification = Notification::find($notification_id);

        if (!$notification) {
            return response()->json([
                "result" => "false",
                "error_code" => 400,
                "error_message" => "Bad request."
            ], 400);
        }

        return response()->json($notification);
    }
}