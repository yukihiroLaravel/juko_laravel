<?php

namespace App\Http\Controllers\Api\Manager;

use App\Dto\Notification\PutDto;
use App\Enums\Notification\StatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\Notification\BulkDeleteRequest;
use App\Http\Requests\Manager\Notification\DeleteRequest;
use App\Http\Requests\Manager\Notification\IndexRequest;
use App\Http\Requests\Manager\Notification\PutRequest;
use App\Http\Requests\Manager\Notification\PutStatusRequest;
use App\Http\Requests\Manager\Notification\ShowRequest;
use App\Http\Requests\Manager\Notification\StoreRequest;
use App\Http\Requests\Manager\Notification\UpdateTypeAllRequest;
use App\Http\Requests\Manager\Notification\UpdateTypeRequest;
use App\Http\Resources\Base\Instructor\NotificationResource;
use App\Http\Resources\Manager\NotificationIndexResource;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Notification;
use App\Services\Notification\BulkDeleteService;
use App\Services\Notification\DeleteService;
use App\Services\Notification\PutNotificationService;
use App\Services\Notification\PutStatusAllService;
use App\Services\Notification\UpdateTypeService;
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

        $instructorId = Auth::guard('instructor')->user()->id;

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
        $instructorId = $request->user()->id;

        $manager = Instructor::with('managings')->find($instructorId);
        assert($manager instanceof Instructor);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        $notification = Notification::with('instructor')->findOrFail($request->notification_id);
        assert($notification instanceof Notification);

        if (!in_array($notification->instructor_id, $instructorIds, true)) {
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

        $manager = Instructor::with('managings')->find($instructorId);
        assert($manager instanceof Instructor);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        $course = Course::findOrFail($request->course_id);
        assert($course instanceof Course);

        if (!in_array($course->instructor_id, $instructorIds, true)) {
            throw new AuthorizationException('Invalid instructor_id.');
        }

        DB::beginTransaction();
        try {
            Notification::create([
                'course_id'       => $request->course_id,
                'instructor_id'   => Auth::guard('instructor')->user()->id,
                'title'           => $request->title,
                'type'            => $request->type,
                'start_date'      => $request->start_date,
                'end_date'        => $request->end_date,
                'status'          => $request->status,
                'content'         => $request->content,
            ]);
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

        $this->authorize('update', $notification);

        DB::beginTransaction();
        try {
            $data = new PutDto(
                type:        $request->type,
                start_date:  $request->start_date,
                end_date:    $request->end_date,
                title:       $request->title,
                content:     $request->content,
                status:      $request->status
            );

            $service($notification, $data);

            DB::commit();

            return response()->json(['result' => true]);
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
        $notification = Notification::findOrFail($request->notification_id);

        $this->authorize('delete', $notification);

        DB::beginTransaction();
        try {
            $service(notification: $notification);

            DB::commit();

            return response()->json(['result' => true]);
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
        $instructorId = Auth::guard('instructor')->user()->id;

        /** @var Instructor $manager */
        $manager = Instructor::with('managings')->find($instructorId);

        $notifications = Notification::whereIn('id', $request->notifications)->get();
        $allowedInstructorIds = $manager->managings->pluck('id')->toArray();
        $allowedInstructorIds[] = $manager->id;

        $service(
            notifications:        $notifications,
            allowedInstructorIds: $allowedInstructorIds,
            type:                  $request->notification_type
        );

        return response()->json(['result' => true]);
    }

    /**
     * お知らせ一覧-タイプ一括変更API
     */
    public function updateTypeAll(UpdateTypeAllRequest $request): JsonResponse
    {
        $instructorId = Auth::guard('instructor')->user()->id;

        /** @var Instructor $manager */
        $manager = Instructor::with('managings')->find($instructorId);

        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        $notifications = Notification::whereIn('instructor_id', $instructorIds)->get();

        $this->authorize('bulkUpdate', [Notification::class, $notifications]);

        try {
            Notification::whereIn('instructor_id', $instructorIds)->update([
                'type' => $request->notification_type,
            ]);

            return response()->json(['result' => true]);
        } catch (Exception $e) {
            Log::error($e);
            throw $e;
        }
    }

    /**
     * お知らせ一覧-一括削除API
     */
    public function bulkDelete(BulkDeleteRequest $request, BulkDeleteService $service): JsonResponse
    {
        $notifications = Notification::whereIn('id', $request->notifications)->get();

        $this->authorize('bulkDelete', [Notification::class, $notifications]);

        DB::beginTransaction();
        try {
            $service($notifications);

            DB::commit();

            return response()->json(['result' => true]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            throw $e;
        }
    }

    /**
     * お知らせ一覧-ステータス一括変更API
     */
    public function putStatusAll(PutStatusRequest $request, PutStatusAllService $service): JsonResponse
    {
        $instructorId = Auth::guard('instructor')->user()->id;

        /** @var Instructor $manager */
        $manager = Instructor::with('managings')->find($instructorId);

        $allowedInstructorIds = $manager->managings->pluck('id')->toArray();
        $allowedInstructorIds[] = $manager->id;

        $notifications = Notification::whereIn('id', $request->notifications)->get();

        $service(
            notifications:        $notifications,
            allowedInstructorIds: $allowedInstructorIds,
            type:                  $request->notification_type
        );

        return response()->json(['result' => true]);
    }
}
