<?php

namespace App\Http\Controllers\Api\Student;

use Carbon\Carbon;
use App\Model\Student;
use App\Model\Attendance;
use App\Model\Notification;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\Student\NotificationReadResource;

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

    public function show(Request $request)
    {   
        //受講生の情報を取得
        $student = Student::findOrFail($request->user()->id);

        //受講生が受講しているコース情報の取得。pluckで受講しているcourse_idのみ取得。今のDBではcourse_idは1のみ
        $courseIds = Attendance::where('student_id', $student->id)->pluck('course_id')->toArray();

        //DBにあるcourseIdから受講生が受講しているcourse_id(1)が一致した場合そのお知らせ通知を取り出す。
        $notification = Notification::whereIn('course_id', $courseIds)->firstOrFail();

        //お知らせがあった場合、詳細に必要な情報のcourse_id,title,contentのデータのみ取り出して$dataに入れる
        if($notification){
            
            $data = [
                'course_id' => $notification->course_id,
                'title' => $notification->title,
                'content' => $notification->content,
            ];
            
            return response()->json($data, 200);

        }
    }
}
