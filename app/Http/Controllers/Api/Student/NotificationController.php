<?php

namespace App\Http\Controllers\Api\Student;

use App\Dto\Student\Notification\IndexDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\Notification\IndexRequest;
use App\Http\Requests\Student\Notification\MarkReadRequest;
use App\Http\Requests\Student\Notification\ShowRequest;
use App\Http\Resources\Base\Student\NotificationResource;
use App\Http\Resources\Student\NotificationIndexResource;
// --- 旧実装で使っていた import は残しつつ、CI 警告を避けるためコメントアウトにして温存しています。---
/* legacy imports (kept for reference)
use App\Model\CourseDeadline;
use App\Model\Attendance;
use App\Model\Notification;
use App\Model\Student;
use App\Services\CourseDeadlineService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
*/
use App\Services\Notification\ShowService;
use App\Services\Notification\IndexService;
use App\Services\Notification\MarkReadService;
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
     *
     * ※ 期限判定・受講可否などのロジックは ShowService に集約。
     *    旧実装は下の LEGACY ブロックにコメントで保存しています。
     */
    public function show(ShowRequest $request, ShowService $service): NotificationResource
    {
        /** @var \App\Model\Student $student */
        $student = $request->user();

        $notification = $service($student, (int) $request->notification_id);

        return new NotificationResource($notification);
    }

    /* --------------------------------------------------------------------------
     * LEGACY（参考用に残置・実行はしません）
     * 以前は Controller で個別期限/講座設定を直接判定していました。
     * 現在は ShowService 側で以下の優先順位で判定しています：
     *   1) attendances.attendance_deadline（個別期限 最優先）
     *   2) courses.deadline_type = fixed  -> course_deadlines.fixed_date
     *   3) courses.deadline_type = relative -> attendance.created_at + course_deadlines.relative_days
     *   4) courses.deadline_type = none でも course_deadlines に値があれば採用
     *
     * public function show(ShowRequest $request, CourseDeadlineService $deadlineService): NotificationResource
     * {
     *     // ここに以前の実装（Attendance/Notification 取得、個別期限チェック、
     *     // CourseDeadlineService->isExpired(...) など）がありました。
     *     // 今回は ShowService に移管済みです。
     * }
     * ------------------------------------------------------------------------ */
}
