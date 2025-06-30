<?php

namespace App\Http\Controllers\Api\Student;

use App\Enums\Notification\StatusEnum;
use App\Enums\Notification\TypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\Notification\IndexRequest;
use App\Http\Requests\Student\Notification\markReadRequest;
use App\Http\Requests\Student\Notification\ShowRequest;
use App\Http\Resources\Base\Student\NotificationResource;
use App\Http\Resources\Student\NotificationIndexResource;
use App\Http\Resources\Student\NotificationReadResource;
use App\Model\Attendance;
use App\Model\Notification;
use App\Model\Student;
use App\Services\Notification\MarkReadService;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;

/**
 * @tags Student-Notification
 */
class NotificationController extends Controller
{
    /**
     * お知らせ取得API
     */
    public function index(IndexRequest $request): NotificationIndexResource
    {
        // リクエストパラメータ
        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);
        $sortBy = $request->input('sort_by', 'start_date');
        $order = $request->input('order', 'asc');
        /**
         * 既読・未読フィルタ
         * - all（全件。デフォルト）
         * - read（既読のみ）
         * - unread（未読のみ）
         */
        $filter = $request->input('filter', 'all');

        $student = $request->user();
        $courseIds = Attendance::where('student_id', $student->id)->pluck('course_id')->toArray();
        $currentDateTime = CarbonImmutable::now();

        // お知らせ取得
        $query = Notification::with('students', 'course')
            ->whereIn('course_id', $courseIds)
            ->where('status', StatusEnum::PUBLIC)
            ->where('start_date', '<=', $currentDateTime)
            ->where('end_date', '>=', $currentDateTime);

        // 既読データ取得
        if($filter === 'read'){
            $query->whereHas('students', function($q) use ($student){
                $q->where('student_id', $student->id);
            });
        // 未読データ取得
        }elseif($filter === 'unread'){
            $query->whereDoesntHave('students', function ($q) use ($student) {
                $q->where('student_id', $student->id);
            });
        }

        $notifications = $query->orderBy($sortBy, $order)
            ->paginate($perPage, ['*'], 'page', $page);

        return new NotificationIndexResource($notifications);
    }

    /**
     * お知らせ既読登録API(Type->onceのみ)
     *
     * ユーザが確認したお知らせIDを取得
     * viewed_once_notificationsテーブルに登録
     */
    public function markRead(markReadRequest $request, markReadService $service)
    {
        $student = $request->user();
        $notificationIds = $request->input('notification_ids', []);

        $service($student, $notificationIds);

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

        if (! in_array($notification->course_id, $courseIds, true)) {
            throw new AuthorizationException('Forbidden, not allowed to this notification.');
        }

        return new NotificationResource($notification);
    }
}
