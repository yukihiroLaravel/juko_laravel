<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\NotificationIndexRequest;
use App\Http\Resources\NotificationIndexResource;
use App\Model\Notification;
use App\Model\Student;
use App\Model\Attendance;
use Carbon\Carbon;

class NotificationController extends Controller
{
    /**
     * お知らせ一覧取得API
     *
     * @param NotificationIndexRequest $request
     * @return NotificationIndexResource
     */
    public function index(NotificationIndexRequest $request)
    {
        // $studentId = $request->user()->id; // ログイン中の受講生のIDを取得（developマージ後に実装予定）
        $student = Student::findOrFail(1);
        $notifications = $this->getNotifications($student);
        $filteredNotifications = $this->filterAndMarkAsRead($student, $notifications);

        return new NotificationIndexResource($filteredNotifications);
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
