<?php

namespace App\Policies;

use App\Model\Chapter;
use App\Model\Course;
use App\Model\Instructor;

class ChapterPolicy
{
    /**
     * 複数(選択)削除
     */
    public function bulkDelete(Instructor $instructor, Chapter $chapter): bool
    {
        if ($instructor->isManager()) {
            // 管理者の場合、配下の講師の講座も削除可能
            $managerIds = $instructor->managings->pluck('id')->toArray();
            $managerIds[] = $instructor->id;

            return in_array($chapter->course->instructor_id, $managerIds, true);
        }

        // 講師の場合、自分の講座のみ削除可能
        return $instructor->id === $chapter->course->instructor_id->instructor_id;
    }

    public function deleteAll(Instructor $instructor, Course $course): bool
    {
        if ($instructor->isManager()) {
            // 管理者の場合、配下の講師の講座も削除可能
            $managerIds = $instructor->managings->pluck('id')->toArray();
            $managerIds[] = $instructor->id;

            return in_array($course->instructor_id, $managerIds, true);
        }

        // 講師の場合、自分の講座のみ削除可能
        return $instructor->id === $course->instructor_id;
    }
}
