<?php

namespace App\Http\Controllers\Api\Student;

use App\Dto\Student\Notification\IndexDto;
use App\Enums\Notification\TypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\Notification\IndexRequest;
use App\Http\Requests\Student\Notification\MarkReadRequest;
use App\Http\Requests\Student\Notification\ShowRequest;
use App\Http\Resources\Base\Student\NotificationResource;
use App\Http\Resources\Student\NotificationIndexResource;
use App\Model\Attendance;
use App\Model\Notification;
use App\Model\Student;
use App\Services\Notification\IndexService;
use App\Services\Notification\MarkReadService;
use App\Services\Notification\ShowService;
use Illuminate\Http\JsonResponse;

/**
 * @tags Student-Notification
 */
class NotificationController extends Controller
{
    /**
     * お知らせ取得API
     */
    public function index(IndexRequest $request, IndexService $service): NotificationIndexResource
    {
        $dto = new IndexDto(
            studentId: $request->user()->id,
            perPage: (int) $request->input('per_page', 20),
            page: (int) $request->input('page', 1),
            sortBy: $request->input('sort_by', 'start_date'),
            order: $request->input('order', 'asc'),
        );

        $notifications = $service($dto);

        return new NotificationIndexResource($notifications);
    }

    /**
     * お知らせ詳細
     */
    public function show(ShowRequest $request, ShowService $service): NotificationResource
    {
        /** @var Student $student */
        $student = $request->user();

        $response = $service(
            student: $student,
            notificationId: (int) $request->notification_id
        );

        // Policyによる認可処理（本人の受講で、受講期限内であること）
        $this->authorize('viewNotification', $response['attendance']);

        return new NotificationResource([
            'notification' => $response['notification'],
            'attendance_deadline' => $response['attendance']->attendance_deadline?->format('Y-m-d'),
        ]);
    }

    /**
     * お知らせ既読登録API
     */
    public function markRead(MarkReadRequest $request, MarkReadService $service): JsonResponse
    {
        $student = $request->user();

        $notification = Notification::where('id', $request->input('notification_id'))
            ->where('type', TypeEnum::ONCE)
            ->firstOrFail();
        $attendance = Attendance::where('student_id', $student->id)
            ->where('course_id', $notification->course_id)
            ->firstOrFail();

        // 既読を記録する前に、本人の受講で受講期限内であることを確かめる
        $this->authorize('viewNotification', $attendance);

        $service(student: $student, notification: $notification);

        return response()->json([
            'result' => true,
        ]);
    }
}
