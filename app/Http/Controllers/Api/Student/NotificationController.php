<?php

namespace App\Http\Controllers\Api\Student;

use Carbon\Carbon;
use App\Model\Student;
use App\Model\Attendance;
use App\Model\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\NotificationShowRequest;
use App\Http\Resources\Student\NotificationReadResource;
use App\Http\Resources\Student\NotificationShowResource;

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

    /**
     * お知らせ詳細
     *
     * @param NotificationShowRequest $request
     * @return NotificationShowResource|JsonResponse
     */
    public function show(NotificationShowRequest $request)
    {
        /** @var Student $student */
        $student = Student::findOrFail($request->user()->id);

        /** @var array<int> $courseIds */
        $courseIds = Attendance::where('student_id', $student->id)->pluck('course_id')->toArray();

        /** @var Notification $notification */
        $notification = Notification::with(['course'])->findOrFail($request->notification_id);

        if (!in_array($notification->course_id, $courseIds, true)) {
            return response()->json([
                'result' => false,
                'message' => 'Forbidden.',
            ], 403);
        }

        return new NotificationShowResource($notification);
    }
}
