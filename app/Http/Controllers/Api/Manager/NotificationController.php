<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Requests\Manager\NotificationIndexRequest;
use App\Http\Resources\Manager\NotificationIndexResource;
use App\Model\Instructor;
use App\Model\Notification;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\NotificationShowRequest;
use App\Http\Resources\Manager\NotificationShowResource;
use App\Http\Requests\Manager\NotificationUpdateRequest;
use App\Http\Resources\Manager\NotificSSationUpdateResource;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * マネージャー側のお知らせ一覧取得API
     *
     * @param NotificationIndexRequest $request
     * @return NotificationIndexResource
     */
    public function index(NotificationIndexRequest $request)
    {
        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);

        // マネージャーが管理する講師IDを取得
        $instructorId = Auth::guard('instructor')->user()->id;
    }
    /** お知らせ詳細
     *
     * @param NotificationShowRequest $request
     * @return NotificationShowResource|\Illuminate\Http\JsonResponse
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

    /**
     * お知らせ更新API
     *
     * @param NotificationUpdateRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(NotificationUpdateRequest $request)
    {
        // ユーザーID取得
        $instructorId = Auth::guard('instructor')->user()->id;

        // 配下のインストラクター情報を取得
        $manager = Instructor::with('managings')->find($instructorId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $instructorId;

        $notifications = Notification::with(['course'])
                                        ->whereIn('instructor_id', $instructorIds)
                                        ->paginate($perPage, ['*'], 'page', $page);

        return new NotificationIndexResource($notifications);
    }
}
