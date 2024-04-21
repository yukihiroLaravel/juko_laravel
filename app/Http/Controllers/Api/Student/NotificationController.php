<?php

namespace App\Http\Controllers\Api\Student;

use App\Model\Notification;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Requests\Student\NotificationIndexRequest;
use App\Http\Resources\Student\NotificationIndexResource;
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
    // public function index(NotificationIndexRequest $request): NotificationIndexResource
    // {
    //     $perPage = $request->input('per_page', 20);
    //     $page = $request->input('page', 1);

    //     $student = Student::findOrFail($request->user()->id);
    //     // $notifications = Notification::with(['course'])
    //     //     ->where('student_id', Auth::guard('student')->user())
    //     //     ->paginate($perPage, ['*'], 'page', $page);
    //     $notifications = $this->getNotifications($student);

    //     return new NotificationIndexResource($notifications);
    // }

    // private function getNotifications(Student $student)
    // {
    //     $attendances = Attendance::where('student_id', $student->id)->get();
    //     $courseIds = $attendances->pluck('course.id')->toArray();
    //     $currentDateTime = Carbon::now();

    //     return Notification::with('students')
    //         ->whereIn('course_id', $courseIds)
    //         ->where('start_date', '<=', $currentDateTime)
    //         ->where('end_date', '>=', $currentDateTime)
    //         ->get();
    // }

    public function index(Request $request)
    {
        $student = Student::findOrFail($request->user()->id);
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
            ->get(['title', 'content']);
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