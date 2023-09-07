<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request; //postman確認のため仮作成
use App\Http\Resources\NotificationIndexResource;
use App\Model\Notification;
use App\Model\Student;
use Carbon\Carbon;
class NotificationController extends Controller
{
    public function index(Request $request)
    {
        // $studentId = $request->user()->id; // ログイン中の受講生のIDを取得（最後に実装予定）
        $student = Student::findOrFail(1);
        $currentDateTime = Carbon::now();
        $courses = $student->attendance()->with('course')->get();
        $courseIds = $courses->pluck('course.id')->toArray();

        $notifications = Notification::whereIn('course_id', $courseIds)
            ->where('start_date', '<=', $currentDateTime)
            ->where('end_date', '>=', $currentDateTime)
            ->get();

        foreach ($notifications as $notification) {
            if ($notification->type === Notification::TYPE_ONCE) {
                if ($notification->students->contains($student->id)) {
                    continue;
                }
                $notification->students()->attach($student->id);
            }

        $formattedNotifications[] = new NotificationIndexResource($notification);

        }

        return response()->json(['data' => $formattedNotifications]);
    }
}
