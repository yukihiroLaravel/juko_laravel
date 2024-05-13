<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Model\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Instructor\NotificationShowRequest;
use App\Http\Requests\Instructor\NotificationIndexRequest;
use App\Http\Requests\Instructor\NotificationStoreRequest;
use App\Http\Requests\Instructor\NotificationUpdateRequest;
use App\Http\Resources\Instructor\NotificationShowResource;
use App\Http\Resources\Instructor\NotificationIndexResource;

class NotificationController extends Controller
{
    /**
     * お知らせ一覧取得API
     *
     * @param NotificationIndexRequest $request
     * @return NotificationIndexResource
     */
    public function index(NotificationIndexRequest $request): NotificationIndexResource
    {
        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);

        $notifications = Notification::with(['course'])
            ->where('instructor_id', Auth::guard('instructor')->user()->id)
            ->paginate($perPage, ['*'], 'page', $page);

        return new NotificationIndexResource($notifications);
    }

    /**
     * お知らせ詳細
     *
     * @param NotificationShowRequest $request
     * @return NotificationShowResource|JsonResponse
     */
    public function show(NotificationShowRequest $request)
    {
        $notification = Notification::with(['course'])
            ->findOrFail($request->notification_id);

        if ($notification->instructor_id !== Auth::guard('instructor')->user()->id) {
            return response()->json([
                'result' => false,
                'message' => 'Forbidden, not allowed to access this notification.',
            ], 403);
        }

        return new NotificationShowResource($notification);
    }

    /**
     * お知らせ登録
     *
     * @param NotificationStoreRequest $request
     * @return JsonResponse
     */
    public function store(NotificationStoreRequest $request): JsonResponse
    {
        Notification::create([
            'course_id'     => $request->course_id,
            'instructor_id' => Auth::guard('instructor')->user()->id,
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

    /**
     * お知らせ更新API
     *
     * @param NotificationUpdateRequest $request
     * @return JsonResponse
     */
    public function update(NotificationUpdateRequest $request): JsonResponse
    {
        $notification = Notification::findOrFail($request->notification_id);
        $notification->fill([
            'type'  => $request->type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'title' => $request->title,
            'content' => $request->content,
        ])
        ->save();

        return response()->json([
            'result' => true,
        ]);
    }

    /**
    * お知らせ一括削除
    *
    * @param Request $request
    * @return JsonResponse
    */
    public function bulkDelete(Request $request): JsonResponse
    {
        // リクエストから削除対象の通知 ID を取得
        $notificationIds = $request->input('notifications', []);

        // インストラクターのユーザー情報を取得
        $user = Auth::guard('instructor')->user();

        // インストラクターに関連し、かつ指定された通知IDを持つ通知のみ削除
        $deletedCount = Notification::where('instructor_id', $user->id)
            ->whereIn('id', $notificationIds)
            ->delete();

        // 削除された通知の数を確認し、削除が成功したことを示すレスポンスを返す
        if ($deletedCount > 0) {
            return response()->json([
                'result' => true,
            ]);
        }
        else {
            return response()->json([
                'result' => false,
                'message' => 'Failed to delete notifications. invalid notification_id.',
            ], 403);
        }
    }
}
