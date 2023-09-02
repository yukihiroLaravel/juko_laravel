<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Model\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function store(Request $request)
    {
        $data = [
            'course_id'     => $request->course_id,
            // 'instructor_id' => Auth::id(),  // テスト終了後はこちらを使用
            'instructor_id' => $request->instructor_id, // テスト中はこちらを使用
            'title'         => $request->title,
            'type'          => $request->type,
            'start_date'    => $request->start_date,
            'end_date'      => $request->end_date,
            'content'       => $request->content,
        ];

        Notification::create($data);

        return response()->json([
            'result' => true,
            'notification' => 'Notification created successfully!',
        ]);
    }
}
