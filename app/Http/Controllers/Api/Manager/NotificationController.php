<?php

namespace App\Http\Controllers\Api\Manager;

use App\Model\Instructor;
use App\Model\Notification;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\NotificationShowRequest;
use App\Http\Resources\Manager\NotificationShowResource;

class NotificationController extends Controller
{
    /**
     * お知らせ詳細
     *
     * @param NotificationShowRequest $request
     * @return NotificationShowResource
     */
    public function show(NotificationShowRequest $request)
    {
        // ユーザーID取得
        $userId = $request->user()->id;
        // 配下のインストラクター情報を取得
        $manager = Instructor::with('managings')->find($userId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $userId; // 自身のIDをインストラクターIDのリストに追加

        // 指定されたお知らせIDでお知らせを取得
        $notification = Notification::findOrFail($request->notification_id);

        // アクセス権限のチェック
        if (!in_array($notification->instructor_id, $instructorIds, true)) {
            return response()->json([
            'result' => false,
            'message' => 'Forbidden, not allowed to access this notification.',
            ], 403);
        }

        return new NotificationShowResource($notification);
    }
}
