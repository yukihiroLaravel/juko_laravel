<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request; //postman確認のため仮作成
use App\Model\Notification;
use App\Model\Student;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        // $studentId = $request->user()->id; // ログイン中の受講生のIDを取得
        $studentId = Student::findOrFail(1);

        $currentDateTime = Carbon::now(); // 現在の日時を取得

        // 手順1: お知らせが対象期間内のものかチェックして表示する
        $notifications = Notification::where('start_date', '<=', $currentDateTime)
        ->where('end_date', '>=', $currentDateTime)
        ->get();

        foreach ($notifications as $notification) {
            // 手順2: always タイプのお知らせを表示
            if ($notification->type === Notification::TYPE_ALWAYS) {
                $this->markAsViewed($notification, $studentId);
            }

            // 手順3: once タイプのお知らせを表示
            if ($notification->type === Notification::TYPE_ONCE) {
                if (!$this->hasViewed($notification, $studentId)) {
                    $this->markAsViewed($notification, $studentId);
                } else {
                    // すでに表示済みの場合はスキップ
                    continue;
                }
            }
            // 整形
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

    public function markAsViewed($notification, $studentId)
    {
        // お知らせを表示する前に "has_viewed" を true に設定
        $notification->students()->updateExistingPivot($studentId, ['has_viewed' => true]);
    }

    public function hasViewed($notification, $studentId)
    {
        // お知らせが閲覧済みかどうかをチェック
        return $notification->students->where('id', $studentId)->first()->pivot->has_viewed === true;
    }    
}
