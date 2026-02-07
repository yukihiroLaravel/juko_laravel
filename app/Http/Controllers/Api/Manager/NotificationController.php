<?php

namespace App\Http\Controllers\Api\Manager;

use App\Dto\Notification\PutDto;
use App\Enums\Notification\StatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\Notification\DeleteRequest;
use App\Http\Requests\Manager\Notification\IndexRequest;
use App\Http\Requests\Manager\Notification\PutRequest;
use App\Http\Requests\Manager\Notification\PutStatusAllRequest;
use App\Http\Requests\Manager\Notification\PutStatusRequest;
use App\Http\Requests\Manager\Notification\UpdateTypeAllRequest;
use App\Http\Resources\Manager\NotificationIndexResource;
use App\Model\Instructor;
use App\Model\Notification;
use App\Services\Notification\DeleteService;
use App\Services\Notification\PutNotificationService;
use App\Services\Notification\PutStatusAllService;
use App\Services\Notification\PutStatusService;
use App\Services\Notification\UpdateTypeAllService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @tags Manager-Notification
 */
class NotificationController extends Controller
{
    /**
     * お知らせ一覧取得API
     */
    public function index(IndexRequest $request): NotificationIndexResource
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

        $notifications = Notification::with(['course', 'course.courseDeadline', 'instructor'])
            ->whereIn('instructor_id', $instructorIds)
            ->paginate($perPage, ['*'], 'page', $page);

        return new NotificationIndexResource($notifications);
    }

    /**
     * お知らせ更新API
     */
    public function put(PutRequest $request, PutNotificationService $service): JsonResponse
    {
        // 指定されたお知らせIDでお知らせを取得
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
     * お知らせ種別全更新API
     */
    public function updateTypeAll(UpdateTypeAllRequest $request, UpdateTypeAllService $service): JsonResponse
    {
        $manager = Auth::guard('instructor')->user();
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        $notifications = Notification::whereIn('instructor_id', $instructorIds)->get();

        $this->authorize('bulkUpdate', [Notification::class, $notifications]);

        $service(
            instructorIds: $instructorIds,
            notificationType: $request->notification_type
        );

        return response()->json([
            'result' => true,
        ]);
    }

    /**
     * お知らせ一括公開・非公開API
     */
    public function putStatus(PutStatusRequest $request, PutStatusService $service): JsonResponse
    {
        $notificationIds = $request->input('notifications', []);
        $status = $request->input('status');

        $notifications = Notification::whereIn('id', $notificationIds)->get(['id', 'instructor_id', 'status']);

        $this->authorize('bulkUpdate', [Notification::class, $notifications]);

        DB::beginTransaction();

        try {
            $service(
                notifications: $notifications,
                status: $status
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
     * お知らせステータス一括変更API
     */
    public function putStatusAll(PutStatusAllRequest $request, PutStatusAllService $service): JsonResponse
    {
        // ログイン中のマネージャーIDを取得
        $instructorId = Auth::guard('instructor')->user()->id;

        // 管理下の講師IDをすべて取得
        /** @var Instructor $manager */
        $manager = Instructor::with('managings')->find($instructorId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        $status = StatusEnum::from($request->status);

        // 対象の通知をすべて取得（管理下の講師に紐づく）
        $notifications = Notification::whereIn('instructor_id', $instructorIds)->get(['id', 'instructor_id', 'status']);

        // 講師と一致しないお知らせが含まれている場合はエラー
        $this->authorize('bulkUpdate', [Notification::class, $notifications]);

        // 一括更新サービス
        $service($status, $notifications);

        return response()->json([
            'result' => true,
        ]);
    }
}
