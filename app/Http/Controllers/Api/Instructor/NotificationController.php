<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\NotificationStoreRequest;
use App\Model\Course;
use App\Model\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = Notification::with(['course'])->get();

        $modifiedNotifications = $notifications->map(function ($notification) {
            return [
                "id" => $notification->id,
                "course_id" => $notification->course_id,
                "course_title" => $notification->course->title,
                "title" => $notification->title,
                "type" => $notification->type,
                "start_date" => $notification->start_date,
            ];
        });

        return response()->json([
            'notifications' => $modifiedNotifications,
        ]);
    }

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
}
