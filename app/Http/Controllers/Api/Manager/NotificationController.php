<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\Instructor;
// use App\Http\Requests\manager\NotificationUpdateRequest;
// use App\Http\Resources\manager\NotificationUpdateResource;
use App\Model\Notification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
        /**
     * お知らせ更新API
     *
     * @param   NotificationUpdateRequest $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function update(Request $request)
    // ※NotificationUpdateRequestは後で作成
    // public function update(NotificationUpdateRequest $request)
    {
        $instructorId = Auth::guard('instructor')->user()->id;
        $manager = Instructor::with('managings')->find($instructorId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $instructorId;
        $notification = Notification::findOrFail($request->notification_id);

        // 自分のお知らせ、または、配下instructorのお知らせでなければエラー応答
        if (!in_array($notification->instructor_id, $instructorIds, true)) {
            //エラー応答
            return response()->json([
                'result' => false,
                'message' => "Forbidden, not allowed to update this notification.",
            ], 403);
        }

        $notification->fill([
            'type' => $request->type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'title' => $request->title,
            'content' => $request->content,
        ])
        ->save();

        //※仮
        $testResource = [
            'type' => $notification->type,
            'start_date' => $notification->start_date,
            'end_date' => $notification->end_date,
            'title' => $notification->title,
            'content' => $notification->content,
        ];

        return response()->json([
            'result' => true,
            'data' => $testResource,
            // ※NotificationUpdateResourceは後で作成
            // 'data' => new NotificationUpdateResource($notification),
        ]);
    }
}
