<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\NotificationStoreRequest;
use App\Http\Requests\Instructor\NotificationShowRequest;
use App\Model\Notification;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\NotificationShowResource;

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

    public function show(NotificationShowRequest $request)
    {
        $validatedData = $request->validated();

        // バリデーションに合格した場合、データベースから通知情報を取得
        $notification = Notification::findOrFail($validatedData['notification_id']);
        // 通知情報をデータベースから取得
        $notification = Notification::findOrFail($notification_id);
        // データベースから通知情報を取得
        $notification = Notification::findOrFail($request->notification_id);

        return new NotificationShowResource($notification);
    }
}
