<?php

namespace App\Services\LessonAttendance;

use App\Enums\LessonAttendance\StatusEnum as LessonAttendanceStatusEnum;
use App\Model\Attendance;
use App\Model\Lesson;
use App\Model\LessonAttendance;

class GenerateForPublishedLessonService
{
    /**
     * 公開されたレッスンに対して、既存受講者全員分の受講状況を一括生成する
     *
     * @param  Lesson  $lesson  対象のレッスン
     */
    public function __invoke(Lesson $lesson): void
    {
        // 1. 対象レッスンの course_id を持つ attendance レコードの id 一覧を取得
        $attendanceIds = Attendance::where('course_id', $lesson->chapter->course_id)
            ->pluck('id');

        // 受講生がまだ誰もいない場合は、何もせず終了
        if ($attendanceIds->isEmpty()) {
            return;
        }

        // 2. 既に同じ (attendance_id, lesson_id) の組み合わせでデータが存在するか確認する（重複防止）
        $existingAttendanceIds = LessonAttendance::where('lesson_id', $lesson->id)
            ->whereIn('attendance_id', $attendanceIds)
            ->pluck('attendance_id');

        // 3. 存在しない attendance_id のみを対象に、LessonAttendance レコードを一括生成する
        $insertDataCollection = $attendanceIds->diff($existingAttendanceIds)
            ->map(fn ($attendanceId) => [
                'attendance_id' => $attendanceId,
                'lesson_id' => $lesson->id,
                'status' => LessonAttendanceStatusEnum::BEFORE_ATTENDANCE,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        // 4. データがある場合のみ一括挿入
        if ($insertDataCollection->isNotEmpty()) {
            LessonAttendance::insert($insertDataCollection->toArray());
        }
    }
}
