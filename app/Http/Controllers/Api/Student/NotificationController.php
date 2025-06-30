<?php

namespace App\Http\Controllers\Api\Student;

use App\Enums\Notification\StatusEnum;
use App\Enums\Notification\TypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\Notification\IndexRequest;
use App\Http\Requests\Student\Notification\ShowRequest;
use App\Http\Resources\Base\Student\NotificationResource;
use App\Http\Resources\Student\NotificationIndexResource;
use App\Http\Resources\Student\NotificationReadResource;
use App\Model\Attendance;
use App\Model\Notification;
use App\Model\Student;
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
        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);
        $sortBy = $request->input('sort_by', 'start_date');
        $order = $request->input('order', 'asc');
        $student = $request->user();
        $courseIds = Attendance::where('student_id', $student->id)->pluck('course_id')->toArray();
        $currentDateTime = CarbonImmutable::now();

        $notifications = Notification::with('course')
            ->whereIn('course_id', $courseIds)
            ->where('status', StatusEnum::PUBLIC)
            ->where('start_date', '<=', $currentDateTime)
            ->where('end_date', '>=', $currentDateTime)
            ->orderBy($sortBy, $order)
            ->paginate($perPage, ['*'], 'page', $page);

        return new NotificationIndexResource($notifications);
    }

    /**
     * お知らせ既読API
     *
     * @return NotificationReadResource
     */
    public function read(Request $request)
    {
        $student = Student::findOrFail($request->user()->id);
        $notifications = $this->getNotifications($student);
        $filteredNotifications = $this->filterAndMarkAsRead($student, $notifications);

        return new NotificationReadResource($filteredNotifications);
    }

    private function getNotifications(Student $student)
    {
        $attendances = Attendance::where('student_id', $student->id)->get();
        $courseIds = $attendances->pluck('course_id')->toArray();
        $currentDateTime = CarbonImmutable::now();

        return Notification::with('students')
            ->whereIn('course_id', $courseIds)
            ->where('start_date', '<=', $currentDateTime)
            ->where('end_date', '>=', $currentDateTime)
            ->get();
    }

    private function filterAndMarkAsRead(Student $student, $notifications)
    {
        return $notifications->filter(function ($notification) use ($student) {
            if ($notification->type === TypeEnum::ONCE) {
                if ($notification->students->contains($student->id)) {
                    return false;
                }
                $notification->students()->attach($student->id);
            }

            return true;
        });
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
