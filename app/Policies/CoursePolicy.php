<?php

namespace App\Policies;

use App\Model\Course;
use App\Model\Instructor;

class CoursePolicy
{
    /**
     * 講師本人またはその講師が管理している講師の講座であれば更新を許可する
     */
    public function update(Instructor $instructor, Course $course): bool
    {
        // 本人の場合は許可
        if ($instructor->id === $course->instructor_id) {
            return true;
        }

        // 管理している講師の中に対象講座の講師が含まれているかチェック
        $manager = Instructor::with('managings')->find($instructor->id);

        if (!$manager) {
            return false;
        }

        $instructorIds = $manager->managings->pluck('id')->toArray();

        if (in_array($course->instructor_id, $instructorIds)) {
            return true;
        }

        return false;
    }
}
