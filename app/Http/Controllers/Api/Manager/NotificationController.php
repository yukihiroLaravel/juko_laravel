<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\Notification\BulkDeleteRequest;
use App\Http\Requests\Manager\Notification\DeleteRequest;
use App\Http\Requests\Manager\Notification\IndexRequest;
use App\Http\Requests\Manager\Notification\PutRequest;
use App\Http\Requests\Manager\Notification\ShowRequest;
use App\Http\Requests\Manager\Notification\StoreRequest;
use App\Http\Requests\Manager\Notification\UpdateTypeAllRequest;
use App\Http\Requests\Manager\Notification\UpdateTypeRequest;
use App\Http\Resources\Base\Instructor\NotificationResource;
use App\Http\Resources\Manager\NotificationIndexResource;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Notification;
use App\Model\ViewedOnceNotification;
use App\Services\Course\DeleteService;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
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

        $notifications = Notification::with(['course', 'instructor'])
            ->whereIn('instructor_id', $instructorIds)
            ->paginate($perPage, ['*'], 'page', $page);

        return new NotificationIndexResource($notifications);
    }

    /**
     * お知らせ詳細
     */
    public function show(ShowRequest $request): NotificationResource
    {
        // ユーザーID取得
        $instructorId = $request->user()->id;

        // 配下のインストラクター情報を取得
        $manager = Instructor::with('managings')->find($instructorId);
        assert($manager instanceof Instructor);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        // 指定されたお知らせIDでお知らせを取得
        $notification = Notification::with('instructor')->findOrFail($request->notification_id);
        assert($notification instanceof Notification);

        // アクセス権限のチェック
        if (! in_array($notification->instructor_id, $instructorIds, true)) {
            throw new AuthorizationException('Forbidden, invalid instructor_id.');
        }

        return new NotificationResource($notification);
    }

    /**
     * お知らせ登録API
     */
    public function store(StoreRequest $request): JsonResponse
    {
        $instructorId = Auth::guard('instructor')->user()->id;

        // 配下のインストラクター情報を取得
        $manager = Instructor::with('managings')->find($instructorId);
        assert($manager instanceof Instructor);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        $course = Course::findOrFail($request->course_id);
        assert($course instanceof Course);
        if (! in_array($course->instructor_id, $instructorIds, true)) {
            throw new AuthorizationException('Invalid instructor_id.');
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
                'status' => $request->status,
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
    public function put(PutRequest $request): JsonResponse
    {
        // 指定されたお知らせIDでお知らせを取得
        $notification = Notification::with('course')->findOrFail($request->notification_id);

        // policyによる認可チェック
        $this->authorize('update', $notification);

        DB::beginTransaction();
        try {
            $notification->fill([
                'type' => $request->type,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'title' => $request->title,
                'content' => $request->content,
                'status' => $request->status,
            ])
                ->save();
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
        // 認証している講師のIDを取得
        $instructorId = Auth::guard('instructor')->user()->id;

        // 配下の講師情報を取得
        $manager = Instructor::with('managings')->find($instructorId);
        assert($manager instanceof Instructor);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        // 指定されたお知らせを取得
        $notification = Notification::findOrFail($request->notification_id);
        assert($notification instanceof Notification);

        // アクセス権限のチェック
        if (! in_array($notification->instructor_id, $instructorIds, true)) {
            throw new AuthorizationException('Invalid instructor_id.');
        }

        DB::beginTransaction();
        try {
            // DeleteServiceを呼び出し削除処理
            $service($notification);

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
    public function updateType(UpdateTypeRequest $request): JsonResponse
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
            throw new AuthorizationException('Invalid instructor_id.');
        }

        $notificationType = $request->notification_type;

        DB::beginTransaction();
        try {
            $notifications->each(function (Notification $notification) use ($notificationType) {
                $notification->fill([
                    'type' => $notificationType,
                ])->save();
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

    public function updateTypeAll(UpdateTypeAllRequest $request)
    {
        // ログインしている講師のIDを取得
        $instructorId = Auth::guard('instructor')->user()->id;

        // 配下の講師情報を取得
        $manager = Instructor::with('managings')->find($instructorId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        // ログイン講師のお知らせを取得
        $notifications = Notification::whereIn('instructor_id', $instructorIds);

        try {
            $notifications->update([
                'type' => $request->notification_type,
            ]);

            return response()->json([
                'result' => true,
            ]);
        } catch (Exception $e) {
            Log::error($e);
            throw $e;
        }
    }

    /**
     * お知らせ一覧-一括削除API
     */
    public function bulkDelete(BulkDeleteRequest $request): JsonResponse
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
        $notificationIds = $notifications->pluck('id')->toArray();
        $notificationsInstructorIds = $notifications->pluck('instructor_id')->toArray();

        // アクセス権のチェック
        if (array_diff($notificationsInstructorIds, $instructorIds) !== []) {
            throw new AuthorizationException('Invalid instructor_id.');
        }

        DB::beginTransaction();
        try {
            // お知らせの閲覧状態を削除
            ViewedOnceNotification::whereIn('notification_id', $notificationIds)->delete();

            // お知らせを削除
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
