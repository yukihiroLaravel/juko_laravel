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
use App\Services\Notification\UpdateTypeService;
use App\Services\Notification\UpdateTypeAllService;
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
     * お知らせ詳細
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
     * お知らせ登録
     */
    public function store(StoreRequest $request): JsonResponse
    {
        $course = Course::findOrFail($request->course_id);

        if ($course->instructor_id !== Auth::guard('instructor')->user()->id) {
            throw new AuthorizationException('Forbidden, invalid instructor_id.');
        }

        DB::beginTransaction();
        try {
            Notification::create([
                'course_id' => $request->course_id,
                'instructor_id' => Auth::guard('instructor')->user()->id,
                'title' => $request->title,
                'type' => $request->type,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'content' => $request->content,
            ]);
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
     * お知らせ削除
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
     * お知らせ一覧-タイプ変更API
     */
    public function updateType(UpdateTypeRequest $request, UpdateTypeService $service): JsonResponse
    {
        $notifications = Notification::whereIn('id', $request->notifications)->get();
        $instructorId = Auth::guard('instructor')->user()->id;
        if (
            $notifications->contains(fn (Notification $notification) => $notification->instructor_id !== $instructorId)
        ) {
            throw new AuthorizationException('Invalid instructor_id.');
        }
        // サービス呼び出し（認可チェック＋トランザクション処理）
        $allowedInstructorIds = [$instructorId]; // 単一の講師IDを配列にする
        $service(
            notifications: $notifications,
            allowedInstructorIds: $allowedInstructorIds,
            type: $request->notification_type
        );
    
        return response()->json(['result' => true]);
    }

    /**
     * 該当講師お知らせ一覧タイプ　一括変更
     */
    public function updateTypeAll(UpdateTypeAllRequest $request, UpdateTypeAllService $service): JsonResponse
    {
        $instructorId = Auth::guard('instructor')->user()->id;
        $service(
            instructorIds: [$instructorId],
            notificationType: $request->notification_type
        );

        return response()->json([
            'result' => true,
        ]);
    }

    /**
     * お知らせ一括削除
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
     * 選択されたお知らせ 一括公開・非公開API
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
     * お知らせ 一括公開・非公開API
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
