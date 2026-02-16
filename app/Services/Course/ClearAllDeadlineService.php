<?php

namespace App\Services\Course;

use App\Enums\Course\DeadlineTypeEnum;
use App\Model\Course;
use App\Model\CourseDeadline;
use App\Model\Instructor;

class ClearAllDeadlineService
{
    /**
     * @param  int  $managerId  実行マネージャーのID
     * @param array $selectedCourseIds
     */
    public function __invoke(int $managerId, array $selectedCourseIds): void
    {
        /** @var Instructor $manager */
        $manager = Instructor::with('managings')->findOrFail($managerId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $managerId;

         // マネージャー配下かつ、選択された講座だけに限定
        $courseIds = Course::whereIn('instructor_id', $instructorIds)
            ->whereIn('id', $selectedCourseIds)
            ->pluck('id');

        if ($courseIds->isEmpty()) {
            return; // 冪等：対象なしなら何もしない
        }

        // 講座の期限タイプを「なし」に更新
        Course::whereIn('id', $courseIds)->update([
            'deadline_type' => DeadlineTypeEnum::NONE,
        ]);

        // 講座の受講期限を削除
        CourseDeadline::whereIn('course_id', $courseIds)->delete();
    }
}
