<?php

namespace App\Http\Controllers\Api\Student;

use App\Dto\Student\Notification\IndexDto;
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
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

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
            filter: $request->input('filter', 'read'),
        );

        $notifications = $service($dto);

        return new NotificationIndexResource($notifications);
    }

    /**
     * お知らせ既読登録API
     */
    public function markRead(MarkReadRequest $request, MarkReadService $service): JsonResponse
    {
        $student = $request->user();
        $notificationId = $request->input('notification_id');

        // サービスクラス呼び出し(登録処理)
        $service(
            student: $student,
            notificationId: $notificationId
        );

        return response()->json([
            'result' => true,
        ]);
    }

    /**
     * お知らせ詳細
     */
    public function show(ShowRequest $request): NotificationResource
    {
        /** @var Student $student */
        $student = Student::findOrFail($request->user()->id);

        /** @var array<int> $courseIds */
        $courseIds = Attendance::where('student_id', $student->id)->pluck('course_id')->toArray();

        /** @var Notification $notification */
        $notification = Notification::with(['course'])
            ->public()
            ->findOrFail($request->notification_id);
        
        // === 受講期限切れチェック：前日までOK／締切日当日からNG ===
        $course = $notification->course;
        if ($course && $course->attendance_deadline) {
            $tz = new DateTimeZone(config('app.timezone'));

            // 今日（アプリTZ）の Y-m-d
            $today = (new DateTimeImmutable('today', $tz))->format('Y-m-d');

            // 期限日を Y-m-d に正規化（string/DateTime/Carbon いずれでもOKにする）
            $deadline = $course->attendance_deadline;
            if ($deadline instanceof DateTimeInterface) {
                $dueDate = DateTimeImmutable::createFromInterface($deadline)->setTimezone($tz)->format('Y-m-d');
            } else {
                $dueDate = (new DateTimeImmutable($deadline, $tz))->format('Y-m-d');
            }

            // ★ 前日までOK：締切日当日（today == dueDate）から期限切れ
            if ($today >= $dueDate) {
                throw new AuthorizationException('The course has expired.');
            }
        }

        if (! in_array($notification->course_id, $courseIds, true)) {
            throw new AuthorizationException('Forbidden, not allowed to this notification.');
        }

        return new NotificationResource($notification);
    }
}
