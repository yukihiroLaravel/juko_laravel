<?php

namespace App\Services\LessonAttendance;

use App\Model\Lesson;
use App\Model\Attendance;
use App\Model\LessonAttendance;

class GenerateForPublishedLessonService
{
    /**
     * 公開されたレッスンに対して、既存受講者全員分の受講状況を一括生成する
     *
     * @param Lesson $lesson 対象のレッスン
     * @return void
     */
    public function execute(Lesson $lesson): void
    {
        // 1. このレッスンが属する「講座（course_id）」を買い受けている生徒のID一覧を取得する
        //    (対象の course_id を持っている attendance レコードを検索)
        $attendanceIds = Attendance::where('course_id', $lesson->chapter->course_id)
            ->pluck('id')
            ->toArray();

        // 受講生がまだ誰もいない場合は、何もせず終了
        if ($attendanceIds->isEmpty()) {
            return;
        }

        // 2. 既に同じ (attendance_id, lesson_id) の組み合わせでデータが存在するか確認する（重複防止）
        $existingAttendanceIds = LessonAttendance::where('lesson_id', $lesson->id)
            ->whereIn('attendance_id', $attendanceIds)
            ->pluck('attendance_id')
            ->toArray();

         //3. 存在しないIDだけを抽出（差分をとる）
        $newAttendanceIds = collect($attendanceIds)->diff($existingAttendanceIds);
        //  そのIDをデータ形式に変換（map）
        $insertData = $newAttendanceIds->map(function ($attendanceId) use ($lesson) {
          return [
                'attendance_id' => $attendanceId,
                'lesson_id' => $lesson->id,
                'status' => LessonAttendance::STATUS_BEFORE_ATTENDANCE,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->toArray();

        // 4. データがある場合のみ、1回のクエリでまとめてデータベースに保存（N+1問題の回避）
            // データを一括で作成するための配列準備が終わった後
            $insertDataCollection = collect($insertData); 
            // ここで初めて insert を呼び出す（これが1回だけ実行される）
            if ($insertDataCollection->isNotEmpty()) {
                LessonAttendance::insert($insertDataCollection->toArray());
        }
    }
}
    