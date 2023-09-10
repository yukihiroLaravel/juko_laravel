<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\NotificationStoreRequest;
use App\Model\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function store(NotificationStoreRequest $request)
    {
        Notification::create([
            'course_id'     => $request->course_id,
            'instructor_id' => 1,
            'title'         => $request->title,
            'type'          => $request->type,
            'start_date'    => $request->start_date,
            'end_date'      => $request->end_date,
            'content'       => $request->content,
        ]);

        return response()->json([
            'result' => true,
        ]);
    }

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
