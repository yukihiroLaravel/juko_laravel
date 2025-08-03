<?php

namespace App\Policies;

use App\Model\Course;
use App\Model\Instructor;

class CoursePolicy
{
    public function update(Instructor $instructor, Course $course): bool
    {
        if ($instructor->isManager()) {
            // 管理者の場合、配下の講師の講座も更新可能
            $managerIds = $instructor->managings->pluck('id')->toArray();
            $managerIds[] = $instructor->id;

            return in_array($course->instructor_id, $managerIds, true);
        }

        // 講師の場合、自分の講座のみ更新可能
        return $instructor->id === $course->instructor_id;
    }

    public function delete(Instructor $instructor, Course $course): bool
    {
        // マネージャー権限のある講師か判定
        if ($instructor->isManager()) {
            $instructorIds = $instructor->managings->pluck('id')->toArray();
            $instructorIds[] = $instructor->id;

            return in_array($course->instructor_id, $instructorIds, true);
        }

        // マネージャー権限のない講師
        return $instructor->id === $course->instructor_id;
    }

    public function create(Instructor $user, Course $course): bool
    {            
        // マネージャーの場合、配下の講師の講座も可能
        if ($user->isManager()) {
            $instructorIds = $user->managings->pluck('id')->toArray();
            $instructorIds[] = $user->id;

            return in_array($course->instructor_id, $instructorIds, true);
        }

        // 講師の場合、自分のコースだけを許可
        return $course->instructor_id === $user->id;
    }
}
