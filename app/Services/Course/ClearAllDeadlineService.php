<?php

namespace App\Services\Course;

use App\Model\Course;
use App\Model\CourseDeadline;
use App\Model\Instructor;

class ClearAllDeadlineService
{
    /**
     * @param int $managerId 実行マネージャーのID
     */
    public function __invoke(int $managerId): void
    {
        // マネージャー + 配下講師のID一覧
        /** @var Instructor $manager */
        $manager = Instructor::with('managings')->findOrFail($managerId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $managerId;

        // 対象コースID
        $courseIds = Course::whereIn('instructor_id', $instructorIds)->pluck('id');

        if ($courseIds->isEmpty()) {
            return; // 冪等：対象なしなら何もしない
        }

        // 1) コースの期限タイプを none に更新
        Course::whereIn('id', $courseIds)->update([
            'deadline_type' => 'none',
        ]);

        // 2) 該当コースの course_deadlines を削除
        CourseDeadline::whereIn('course_id', $courseIds)->delete();
    }
}