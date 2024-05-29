<?php

namespace App\Http\Controllers\Api\Manager;

use Exception;
use App\Model\Instructor;
use App\Model\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Model\ViewedOnceNotification;
use App\Http\Requests\Manager\NotificationShowRequest;
use App\Http\Requests\Manager\NotificationIndexRequest;
use App\Http\Requests\Manager\NotificationUpdateRequest;
use App\Http\Requests\Manager\NotificationBulkDeleteRequest;
use App\Http\Resources\Manager\NotificationShowResource;
use App\Http\Requests\Manager\NotificationPutTypeRequest;
use App\Http\Resources\Manager\NotificationIndexResource;

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
        $notification = Notification::with(['course'])
            ->findOrFail($request->notification_id);

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
        ]);
    }

    /**
     * お知らせ一覧-タイプ変更API
     *
     * @param NotificationPutTypeRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateType(NotificationPutTypeRequest $request)
    {
        // 認証している講師のIDを取得
        $instructorId = Auth::guard('instructor')->user()->id;

        // 配下の講師情報を取得
        /** @var Instructor $manager */
        $manager = Instructor::with('managings')->find($instructorId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        // 選択されたお知らせリストを取得
        $notifications = Notification::whereIn('id', $request->notifications)->get();
        $notificationsInstructorIds = $notifications->pluck('instructor_id')->toArray();

        // アクセス権限のチェック
        if (array_diff($notificationsInstructorIds, $instructorIds) !== []) {
            return response()->json([
                'result' => false,
                'message' => 'Forbidden, not allowed to update this notification.',
            ], 403);
        }

        $type = $request->type;

        DB::beginTransaction();
        try {
            $notifications->each(function (Notification $notification) use ($type) {
                $notification->fill([
                    'type' => $type,
                ])->save();
            });
            DB::commit();
            return response()->json([
                'result' => true,
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return response()->json([
                'result' => false,
            ], 500);
        }
    }

    /**
     * お知らせ一覧-一括削除API
     *
     * @param NotificationBulkDeleteRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkDelete(NotificationBulkDeleteRequest $request): JsonResponse
    {
        // 認証している講師のIDを取得
        $instructorId = Auth::guard('instructor')->user()->id;

        // 配下の講師情報を取得
        /** @var Instructor $manager */
        $manager = Instructor::with('managings')->find($instructorId);
        $instructorIds = $manager->managings->pluck('id')->toArray();

        // 選択されたお知らせリストを取得
        $notifications = Notification::whereIn('id', $request->notifications)->get();
        $notificationIds = $notifications->pluck('id')->toArray();
        $notificationsInstructorIds = $notifications->pluck('instructor_id')->toArray();

        // アクセス権のチェック
        if (
            collect($notificationsInstructorIds)->contains(function ($notificationInstructorId) use ($manager) {
                return $notificationInstructorId !== $manager->id;
            })
        ) {
            return response()->json([
                'result' => false,
                'message' => 'Forbidden, not allowed to update these notifications.',
            ], 403);
        }

        DB::beginTransaction();
        try {
            // viewed_once_notificationsテーブルのレコードを一括削除
            ViewedOnceNotification::whereIn('notification_id', $notificationIds)->delete();

            // notificationsテーブルのレコードを一括削除
            Notification::whereIn('id', $notificationIds)->delete();

            // コミット
            DB::commit();

            return response()->json([
                'result' => true,
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return response()->json([
                'result' => false,
            ], 500);
        }
    }
}
