<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\NotificationBulkDeleteRequest;
use App\Http\Requests\Instructor\NotificationDeleteRequest;
use App\Http\Requests\Instructor\NotificationIndexRequest;
use App\Http\Requests\Instructor\NotificationPutTypeRequest;
use App\Http\Requests\Instructor\NotificationShowRequest;
use App\Http\Requests\Instructor\NotificationStoreRequest;
use App\Http\Requests\Instructor\NotificationUpdateRequest;
use App\Http\Resources\Instructor\NotificationIndexResource;
use App\Http\Resources\Instructor\NotificationShowResource;
use App\Model\Notification;
use App\Model\ViewedOnceNotification;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    /**
     * お知らせ一覧取得API
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
     * @return NotificationShowResource|JsonResponse
     */
    public function show(NotificationShowRequest $request)
    {
        $notification = Notification::with(['course'])
            ->findOrFail($request->notification_id);

        if ($notification->instructor_id !== Auth::guard('instructor')->user()->id) {
            throw new AuthorizationException('Forbidden, not allowed to access this notification.');
        }

        return new NotificationShowResource($notification);
    }

    /**
     * お知らせ登録
     */
    public function store(NotificationStoreRequest $request): JsonResponse
    {
        Notification::create([
            'course_id' => $request->course_id,
            'instructor_id' => Auth::guard('instructor')->user()->id,
            'title' => $request->title,
            'type' => $request->type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'content' => $request->content,
        ]);

        return response()->json([
            'result' => true,
        ]);
    }

    /**
     * お知らせ更新API
     */
    public function update(NotificationUpdateRequest $request): JsonResponse
    {
        $notification = Notification::findOrFail($request->notification_id);
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
     * お知らせ削除
     */
    public function delete(NotificationDeleteRequest $request): JsonResponse
    {
        // 認証している講師のIDを取得
        $instructorId = Auth::guard('instructor')->user()->id;

        // 指定されたお知らせを取得
        /** @var Notification $notification */
        $notification = Notification::findOrFail($request->notification_id);

        // お知らせが、現在ログインしている講師のものでなければエラー
        if ($instructorId !== $notification->instructor_id) {
            throw new AuthorizationException('Invalid instructor_id.');
        }

        DB::beginTransaction();
        try {
            // 中間テーブルにお知らせと生徒の関係があれば行を削除
            $notification->students()->detach();
            $notification->delete();
            DB::commit();

            return response()->json([
                'result' => true,
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            throw $e;
        }
    }

    /**
     * お知らせ一覧-タイプ変更API
     */
    public function updateType(NotificationPutTypeRequest $request): JsonResponse
    {
        $notifications = Notification::whereIn('id', $request->notifications)->get();
        $instructorId = Auth::guard('instructor')->user()->id;

        if (
            $notifications->contains(function (Notification $notification) use ($instructorId) {
                return $notification->instructor_id !== $instructorId;
            })
        ) {
            throw new AuthorizationException(
                'Forbidden, not allowed to access this notification.'
            );
        }
        DB::beginTransaction();
        try {
            $notificationType = $request->notification_type;
            $notifications->each(function ($notification) use ($notificationType) {
                // 指定されたお知らせIDでお知らせを取得
                $notification->fill([
                    'type' => $notificationType,
                ])
                    ->save();
            });
            DB::commit();

            return response()->json([
                'result' => true,
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            throw $e;
        }
    }

    /**
     * お知らせ一括削除
     */
    public function bulkDelete(NotificationBulkDeleteRequest $request): JsonResponse
    {
        $notificationIds = $request->input('notifications', []);

        $instructor = Auth::guard('instructor')->user();

        /** @var Collection<int, Notification> $notifications */
        $notifications = Notification::whereIn('id', $notificationIds)->get();

        // 講師と一致しないお知らせが含まれている場合はエラー
        if (
            $notifications->contains(function (Notification $notification) use ($instructor) {
                return $notification->instructor_id !== $instructor->id;
            })
        ) {
            // 講師と一致しないお知らせが含まれている場合はエラー
            throw new AuthorizationException('Forbidden.');
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
            DB::rollBack();
            Log::error($e);
            throw $e;
        }
    }
}
