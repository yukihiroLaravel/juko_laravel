<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request; //postman確認のため仮作成
use App\Model\Notification;
use App\Model\Student;
use Carbon\Carbon;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        // $studentId = $request->user()->id; // ログイン中の受講生のIDを取得（最後に実装予定）
        $studentId = Student::findOrFail(1);

        $currentDateTime = Carbon::now();

        $notifications = Notification::where('start_date', '<=', $currentDateTime)
            ->where('end_date', '>=', $currentDateTime)
            ->whereDoesntHave('students', function ($query) use ($studentId) {
                $query->where('student_id', $studentId);
            })
            ->get();

        $formattedNotifications = [];

        foreach ($notifications as $notification) {
            if ($notification->type === Notification::TYPE_ONCE) {
                if (!$notification->students->contains($studentId)) {
                    $notification->students()->attach($studentId);
                } else {
                    continue;
                }
            }

            $formattedNotifications[] = [
                'id' => $notification->id,
                'course_id' => $notification->course_id,
                'course_title' => $notification->course->title,
                'type' => $notification->type,
                'title' => $notification->title,
                'content' => $notification->content,
            ];
        }

        return response()->json(['data' => $formattedNotifications]);
    }
}
