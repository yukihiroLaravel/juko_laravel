<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\NotificationIndexRequest;
use App\Http\Resources\Manager\NotificationIndexResource;
use App\Model\Instructor;
use App\Model\Notification;
use App\Http\Requests\Manager\NotificationShowRequest;
use App\Http\Resources\Manager\NotificationShowResource;
use App\Http\Requests\Manager\NotificationUpdateRequest;
use App\Http\Resources\Manager\NotificationUpdateResource;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * お知らせ一覧取得API
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

        // 配下のインストラクター情報を取得
        /** @var Instructor $manager */
        $manager = Instructor::with('managings')->find($instructorId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        $notifications = Notification::with(['course'])
            ->whereIn('instructor_id', $instructorIds)
            ->paginate($perPage, ['*'], 'page', $page);

        return new NotificationIndexResource($notifications);
    }

    /**
     * お知らせ詳細
     *
     * @param NotificationShowRequest $request
     * @return NotificationShowResource|\Illuminate\Http\JsonResponse
     */
    public function show(NotificationShowRequest $request)
    {
        // ユーザーID取得
        $instructorId = $request->user()->id;

        // 配下のインストラクター情報を取得
        /** @var Instructor $manager */
        $manager = Instructor::with('managings')->find($instructorId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        // 指定されたお知らせIDでお知らせを取得
        /** @var Notification $notification */
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
     * お知らせ登録API
     */
    public function store()
    {
        return response()->json([]);
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
        /** @var Instructor $manager */
        $manager = Instructor::with('managings')->find($instructorId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        // 指定されたお知らせIDでお知らせを取得
        /** @var Notification $notification */
        $notification = Notification::findOrFail($request->notification_id);

        // アクセス権限のチェック
        if (!in_array($notification->instructor_id, $instructorIds, true)) {
            return response()->json([
                'result' => false,
                'message' => 'Forbidden, not allowed to update this notification.',
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

        return response()->json([
            'result' => true,
            'data' => new NotificationUpdateResource($notification),
        ]);
    }
}
