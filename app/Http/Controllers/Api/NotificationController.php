<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request; //postman確認のため仮作成
use App\Http\Resources\NotificationIndexResource;
use App\Model\Notification;
use App\Model\Student;
use App\Model\Attendance;
use Carbon\Carbon;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        // $studentId = $request->user()->id; // ログイン中の受講生のIDを取得（developマージ後に実装予定）
        $student = Student::findOrFail(1);
        $notifications = $this->getNotifications($student);
        $formattedNotifications = $this->formatNotifications($student, $notifications);

        return new NotificationIndexResource($formattedNotifications);
    }

    private function getNotifications(Student $student)
    {
        $attendances = Attendance::where('student_id', $student->id)->get();
        $courseIds = $attendances->pluck('course.id')->toArray();
        $currentDateTime = Carbon::now();

        return Notification::whereIn('course_id', $courseIds)
            ->where('start_date', '<=', $currentDateTime)
            ->where('end_date', '>=', $currentDateTime)
            ->with('course')
            ->get();
    }

    private function formatNotifications(Student $student, $notifications)
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
