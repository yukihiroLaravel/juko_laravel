<?php

namespace App\Http\Controllers\Api\Student;

use Carbon\Carbon;
use App\Model\Student;
use App\Model\Attendance;
use App\Model\Notification;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\Student\NotificationReadResource;
use App\Http\Requests\Student\NotificationShowRequest;

class NotificationController extends Controller
{
    /**
     * お知らせ取得API
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

    public function show(NotificationShowRequest $request)
    {
        $student = Student::findOrFail($request->user()->id);
        $courseIds = Attendance::where('student_id', $student->id)->pluck('course_id')->toArray();
        $notification = Notification::with(['course'])->findOrFail($request->notification_id);

        if (!in_array($notification->course_id, $courseIds, true)) {
            return response()->json([
                'result' => false,
                'message' => 'Forbidden, not allowed to access this notification.',
            ], 403);
        }

            $data = [
                'notification_id' => $notification->id,
                'title' => $notification->title,
                'content' => $notification->content,
                "start_date" => $notification->start_date,
                "end_date" => $notification->end_date,
                'course' => [
                    'course_id' => $notification->course->id,
                    'title' => $notification->course->title,
                ],
            ];

            return response()->json($data, 200);
    }
}
