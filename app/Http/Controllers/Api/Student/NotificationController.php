<?php

namespace App\Http\Controllers\Api\Student;

use Carbon\Carbon;
use App\Model\Student;
use App\Model\Attendance;
use App\Model\Notification;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
// use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Student\NotificationIndexRequest;
use App\Http\Resources\Student\NotificationIndexResource;
use App\Http\Resources\Student\NotificationReadResource;

class NotificationController extends Controller
{
    /**
     * お知らせ取得API
     *
     * @param NotificationIndexRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(NotificationIndexRequest $request): NotificationIndexResource
    {
        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);
        $sortBy = $request->input('sort_by', 'start_date');
        $order = $request->input('order', 'asc');

        // ログイン中の受講生を取得
        $student = Student::findOrFail($request->user()->id);

        // 受講生が受講しているコースのIDを取得します。
        $courseIds = Attendance::where('student_id', $student->id)->pluck('course_id')->toArray();

        // 現在の日時を取得
        $currentDateTime = Carbon::now();

        $notifications = Notification::with('course')
            ->whereIn('course_id', $courseIds)
            ->where('start_date', '<=', $currentDateTime)
            ->where('end_date', '>=', $currentDateTime)
            ->orderBy($sortBy, $order)
            ->paginate($perPage, ['*'], 'page', $page);

        return new NotificationIndexResource($notifications);
    }

    /**
     * お知らせ既読API
     *
     * @param Request $request
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
        $courseIds = $attendances->pluck('course.id')->toArray();
        $currentDateTime = Carbon::now();

        return Notification::with('students')
            ->whereIn('course_id', $courseIds)
            ->where('start_date', '<=', $currentDateTime)
            ->where('end_date', '>=', $currentDateTime)
            ->get();
    }

    private function filterAndMarkAsRead(Student $student, $notifications)
    {
        return $notifications->filter(function ($notification) use ($student) {
            if ($notification->type === Notification::TYPE_ONCE) {
                if ($notification->students->contains($student->id)) {
                    return false;
                }
                $notification->students()->attach($student->id);
            }
            return true;
        });
    }
}
