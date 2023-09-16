<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\NotificationShowRequest;
use App\Model\Notification;

class NotificationController extends Controller
{
    public function show(NotificationShowRequest $request)
    {
        // データベースから通知情報を取得
        $notification = Notification::findOrFail($request->notification_id);

        return response()->json($notification);
    }
}