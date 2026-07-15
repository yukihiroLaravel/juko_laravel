<?php

namespace App\Services\LessonAttendance;
use App\Model\Lesson;
use App\Model\LessonAttendance;

class GenerateForPublishedLessonService
{
    /**
     * 公開されたレッスンに対して、既存受講者全員分の受講状況を一括生成する
     *
     * @param  Lesson  $lesson  対象のレッスン
     */
    public function execute(Lesson $lesson): void
    {
    // 受講生がまだ誰もいない場合は、何もせず終了
    $attendanceIds = $lesson->chapter->course->attendances->pluck('id')->toArray();
    if (empty($attendanceIds)) {
        return;
    }

    // 2. 既に同じ組み合わせでデータが存在するか確認する（重複防止）
    $existingAttendanceIds = LessonAttendance::where('lesson_id', $lesson->id)
        ->whereIn('attendance_id', $attendanceIds)
        ->pluck('attendance_id')
        ->toArray();

    // 3. 存在しないIDだけを対象に、データを一括作成する
    $insertDataCollection = collect($attendanceIds)->diff($existingAttendanceIds);

    $insertData = $insertDataCollection->map(function ($attendanceId) use ($lesson) {
        return [
            'attendance_id' => $attendanceId,
            'lesson_id' => $lesson->id,
            'status' => LessonAttendance::STATUS_BEFORE_ATTENDANCE,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    });

    // 4. データがある場合のみ一括挿入
    if ($insertData->isNotEmpty()) {
        LessonAttendance::insert($insertData->toArray());
    }
}
}
