<?php

namespace App\Policies;

use App\Model\Instructor;
use App\Model\Lesson;

class LessonPolicy
{
    public function bulkDelete(Instructor $instructor, Collection $lessons): bool
    {
        if ($instructor->isManager()) {
            // 管理者の場合、配下の講師の講座も削除可能
            $managerIds = $instructor->managings->pluck('id')->toArray();
            $managerIds[] = $instructor->id;
            return $lessons->every(fn (Lesson $lesson) =>
                in_array($lesson->chapter->course->instructor_id, $managerIds, true)
            );
        }
        // 講師の場合、自分の講座のみ削除可能
        return $lessons->every(fn (Lesson $lesson) =>
            $lesson->chapter->course->instructor_id === $instructor->id
        );
    }
}
