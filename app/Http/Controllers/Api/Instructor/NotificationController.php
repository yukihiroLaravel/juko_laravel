<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\Notification;

class NotificationController extends Controller
{
    /**
     * お知らせ更新API
     * @param
     * @return
     */
    public function update(Request $request) {

        $courseId = $request->course_id;
        Notification::findOrFail($courseId)->update([
            'type'  => $request->type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'title' => $request->title,
            'content' => $request->content,
        ]);
        return response()->json([
            'result' => true,
        ]);
    }
}