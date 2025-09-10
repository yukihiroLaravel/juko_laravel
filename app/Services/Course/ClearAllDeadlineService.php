<?php

namespace App\Services\Course;

use App\Model\Course;
use App\Model\CourseDeadline;
use App\Model\Instructor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClearAllDeadlineService
{
    /**
     * マネージャー本人 + 配下講師の全講座の受講期限を「なし」にする
     * - courses.deadline_type = 'none' に更新
     * - course_deadlines を course_id で一括削除
     * - 冪等: 2回実行しても同じ結果
     */
    public function __invoke(): void
    {
        // マネージャーID（instructorガード）
        $managerId = Auth::guard('instructor')->id();

        // マネージャー + 配下講師のID一覧
        /** @var Instructor $manager */
        $manager = Instructor::with('managings')->findOrFail($managerId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $managerId;

        // 対象コースID
        $courseIds = Course::whereIn('instructor_id', $instructorIds)->pluck('id');

        DB::transaction(function () use ($courseIds) {
            if ($courseIds->isEmpty()) {
                return; // 対象なしなら何もしない（冪等）
            }

            // 1) コースの期限タイプを none に更新
            Course::whereIn('id', $courseIds)->update([
                'deadline_type' => 'none',
            ]);

            // 2) 該当コースの course_deadlines を削除
            CourseDeadline::whereIn('course_id', $courseIds)->delete();

            // （もし将来「受講生ごとの期限」テーブルができたらここで同様に削除）
            // AttendanceDeadline::whereIn('course_id', $courseIds)->delete();
        });
    }
}