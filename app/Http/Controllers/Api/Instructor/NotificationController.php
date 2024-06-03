<?php

namespace App\Http\Controllers\Api\Instructor;

use Exception;
use App\Model\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Model\ViewedOnceNotification;
use Illuminate\Database\Eloquent\Collection;
use App\Http\Requests\Instructor\NotificationShowRequest;
use App\Http\Requests\Instructor\NotificationIndexRequest;
use App\Http\Requests\Instructor\NotificationStoreRequest;
use App\Http\Requests\Instructor\NotificationUpdateRequest;
use App\Http\Resources\Instructor\NotificationShowResource;
use App\Http\Requests\Instructor\NotificationPutTypeRequest;
use App\Http\Resources\Instructor\NotificationIndexResource;
use App\Http\Requests\Instructor\NotificationDeleteRequest;
use App\Http\Requests\Instructor\NotificationBulkDeleteRequest;

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
     * お知らせ削除
     *
     * @param NotificationDeleteRequest $request
     * @return JsonResponse
     */
    public function delete(NotificationDeleteRequest $request): JsonResponse
    {
        $instructor = Auth::guard('instructor')->user();
        $notification = Notification::findOrFail($request->notification_id);

        if ($notification->instructor_id !== $instructor->id) {
            return response()->json([
                'result' => false,
                'message' => 'Notification is not associated with the instructor\'s course.',
            ], 403);
        }

        DB::beginTransaction();

        try {
            ViewedOnceNotification::where('notification_id', $notification->id)->delete();
            $notification->delete();

            DB::commit();

            return response()->json([
                'result' => true,
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();

            Log::error($e->getMessage());

            return response()->json([
                'result' => false,
            ], 500);
        }
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
    * お知らせ一括削除
    *
    * @param NotificationBulkDeleteRequest $request
    * @return JsonResponse
    */
    public function bulkDelete(NotificationBulkDeleteRequest $request): JsonResponse
    {
        $notificationIds = $request->input('notifications', []);

        $instructor = Auth::guard('instructor')->user();

        /** @var Collection $notifications */
        $notifications = Notification::whereIn('id', $notificationIds)->get();

        // 講師と一致しないお知らせが含まれている場合はエラー
        if (
            $notifications->contains(function (Notification $notification) use ($instructor) {
                return $notification->instructor_id !== $instructor->id;
            })
        ) {
            // 講師と一致しないお知らせが含まれている場合はエラー
            return response()->json([
                'result' => false,
                'message' => 'Forbidden.',
            ], 403);
        }

        // トランザクション開始
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
            // ロールバック
            DB::rollBack();

            // ログ出力
            Log::debug($e->getMessage());

            // エラーレスポンスを返す
            return response()->json([
                'result' => false,
                'message' => 'Failed to delete notifications.',
            ], 500);
        }
    }
}
