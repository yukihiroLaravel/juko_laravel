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
        if (empty($attendanceIds)) {
            return;
        }

        // 2. 既に同じ (attendance_id, lesson_id) の組み合わせでデータが存在するか確認する（重複防止）
        $existingAttendanceIds = LessonAttendance::where('lesson_id', $lesson->id)
            ->whereIn('attendance_id', $attendanceIds)
            ->pluck('attendance_id')
            ->toArray();

        // 3. データベースに一括挿入（insert）するためのデータ配列（箱）を作る
        $insertData = [];
        
        foreach ($attendanceIds as $attendanceId) {
            // すでにデータが存在する受講生はスキップ（冪等性の担保）
            if (in_array($attendanceId, $existingAttendanceIds)) {
                continue;
            }

            // 一括保存するためのデータを準備
            $insertData[] = [
                'attendance_id' => $attendanceId,
                'lesson_id' => $lesson->id,
                'status' => 'before_attendance', // 仕様書の「未着手」ステータス（※プロジェクトの定数やEnumがあれば適宜合わせる）
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // 4. データがある場合のみ、1回のクエリでまとめてデータベースに保存（N+1問題の回避）
        if (!empty($insertData)) {
            LessonAttendance::insert($insertData);
        }
    }
}