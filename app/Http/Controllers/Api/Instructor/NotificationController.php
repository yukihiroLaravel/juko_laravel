<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Model\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function show(Request $request, $notification_id)
    {
        // 通知情報をデータベースから取得
        $notification = Notification::findOrFail($notification_id);

        return response()->json($notification);
    }
}