<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Dto\Notification\PutDto;
use App\Enums\Notification\StatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\Notification\BulkDeleteRequest;
use App\Http\Requests\Instructor\Notification\DeleteRequest;
use App\Http\Requests\Instructor\Notification\IndexRequest;
use App\Http\Requests\Instructor\Notification\PutRequest;
use App\Http\Requests\Instructor\Notification\PutStatusAllRequest;
use App\Http\Requests\Instructor\Notification\PutStatusRequest;
use App\Http\Requests\Instructor\Notification\ShowRequest;
use App\Http\Requests\Instructor\Notification\StoreRequest;
use App\Http\Requests\Instructor\Notification\UpdateTypeAllRequest;
use App\Http\Requests\Instructor\Notification\UpdateTypeRequest;
use App\Http\Resources\Base\Instructor\NotificationResource;
use App\Http\Resources\Instructor\NotificationIndexResource;
use App\Model\Course;
use App\Model\Notification;
use App\Services\Notification\BulkDeleteService;
use App\Services\Notification\DeleteService;
use App\Services\Notification\PutNotificationService;
use App\Services\Notification\PutStatusAllService;
use App\Services\Notification\StoreNotificationService;
use App\Services\Notification\UpdateTypeAllService;
use App\Services\Notification\UpdateTypeService;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @tags Instructor-Notification
 */
class NotificationController extends Controller
{
    /**
     * お知らせ一覧取得API
     */
    public function index(IndexRequest $request): NotificationIndexResource
    {
        $instructorId = Auth::guard('instructor')->user()->id;
        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);

        $notifications = Notification::with(['course.tags'])
            ->where('instructor_id', $instructorId)
            ->paginate($perPage, ['*'], 'page', $page);

        return new NotificationIndexResource($notifications);
    }

    /**
     * お知らせ詳細API
     */
    public function show(ShowRequest $request): NotificationResource
    {
        $notification = Notification::with(['course'])
            ->findOrFail($request->notification_id);

        if ($notification->instructor_id !== Auth::guard('instructor')->user()->id) {
            throw new AuthorizationException('Invalid instructor_id.');
        }

        return new NotificationResource($notification);
    }

    /**
     * お知らせ登録API
     */
    public function store(StoreRequest $request, StoreNotificationService $service): JsonResponse
    {
        // 講座取得
        $course = Course::findOrFail($request->course_id);

        // Policyを使った認可チェック
        if (Gate::denies('create', $course)) {
            throw new AuthorizationException('Forbidden, invalid instructor_id.');
        }

        // 講師IDは認可済みなので、ログインユーザーから取得
        $instructorId = Auth::guard('instructor')->user()->id;

        DB::beginTransaction();
        try {
            $service(
                course_id: $request->course_id,
                instructor_id: $instructorId,
                title: $request->title,
                type: $request->type,
                start_date: $request->start_date,
                end_date: $request->end_date,
                content: $request->content,
                status: $request->status
            );

            DB::commit();

            return response()->json(['result' => true]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            throw $e;
        }
    }

    /**
     * お知らせ更新API
     */
    public function put(PutRequest $request, PutNotificationService $service): JsonResponse
    {
        $notification = Notification::findOrFail($request->notification_id);

        // policyによる認可チェック
        $this->authorize('update', $notification);

        DB::beginTransaction();
        try {
            $data = new PutDto(
                type: $request->type,
                start_date: $request->start_date,
                end_date: $request->end_date,
                title: $request->title,
                content: $request->content,
                status: $request->status
            );

            $service(
                $notification,
                $data
            );

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
     * お知らせ削除API
     */
    public function delete(DeleteRequest $request, DeleteService $service): JsonResponse
    {
        // 指定されたお知らせを取得
        $notification = Notification::findOrFail($request->notification_id);

        // policyによる認可チェック
        $this->authorize('delete', $notification);

        DB::beginTransaction();
        try {
            $service(notification: $notification);

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
     * お知らせ種別一括更新API
     */
    public function updateType(UpdateTypeRequest $request, UpdateTypeService $service): JsonResponse
    {
        $notifications = Notification::whereIn('id', $request->notifications)->get();

        // policyによる認可チェック
        $this->authorize('bulkUpdate', [Notification::class, $notifications]);

        DB::beginTransaction();
        try {
            $service(
                notifications: $notifications,
                type: $request->notification_type
            );

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
     * お知らせ種別全更新API
     */
    public function updateTypeAll(UpdateTypeAllRequest $request, UpdateTypeAllService $service): JsonResponse
    {
        $instructorId = Auth::guard('instructor')->user()->id;

        $notifications = Notification::whereIn('instructor_id', [$instructorId])->get();

        $this->authorize('bulkUpdate', [Notification::class, $notifications]);

        $service(
            instructorIds: [$instructorId],
            notificationType: $request->notification_type
        );

        return response()->json([
            'result' => true,
        ]);
    }

    /**
     * お知らせ一括削除API
     */
    public function bulkDelete(BulkDeleteRequest $request, BulkDeleteService $service): JsonResponse
    {
        $notifications = Notification::whereIn('id', $request->notifications)->get();

        // 講師と一致しないお知らせが含まれている場合はエラー
        $this->authorize('bulkDelete', [Notification::class, $notifications]);

        // トランザクション開始
        DB::beginTransaction();

        try {
            // viewed_once_notifications, notificationsテーブルのレコードを一括削除するサービス
            $service($notifications);

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

    /**
     * お知らせステータス更新API
     */
    public function putStatus(PutStatusRequest $request): JsonResponse
    {
        // ログインしている講師のIDを取得
        $instructorId = Auth::guard('instructor')->user()->id;

        // 選択されたお知らせidを取得
        $notificationIds = $request->input('notifications', []);

        // 選択されたお知らせを取得
        $chosenNotifications = Notification::whereIn('id', $notificationIds)->pluck('instructor_id');

        // 選択されたお知らせの中に、講師と一致しないお知らせが、１つでも含まれている場合はエラー
        if (
            $chosenNotifications->contains(fn ($instructorIdFromNotificationsTable) => $instructorIdFromNotificationsTable !== $instructorId)
        ) {
            throw new AuthorizationException('Invalid instructor_id.');
        }

        // トランザクション開始
        DB::beginTransaction();

        try {
            Notification::where('instructor_id', $instructorId)
                ->whereIn('id', $notificationIds)
                ->update(['status' => $request->status]);

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

    /**
     * お知らせ状態一括更新API
     */
    public function putStatusAll(PutStatusAllRequest $request, PutStatusAllService $service): JsonResponse
    {
        $instructorId = Auth::guard('instructor')->user()->id;

        $status = StatusEnum::from($request->status);

        $notifications = Notification::where('instructor_id', $instructorId)->get(['id', 'instructor_id', 'status']);

        // 講師と一致しないお知らせが含まれている場合はエラー
        $this->authorize('bulkUpdate', [Notification::class, $notifications]);

        $service($status, $notifications);

        return response()->json([
            'result' => true,
        ]);
    }
}
