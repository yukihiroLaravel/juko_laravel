<?php

namespace App\Policies;

use App\Model\Instructor;
use Illuminate\Database\Eloquent\Collection;
use App\Model\Course;
use Illuminate\Support\Facades\Log;

class ChapterPolicy
{
    /**
     * 複数(選択)削除
     */
    public function bulkDelete(Instructor $instructor, Collection $chapters, int $courseId): bool
    {
        // マネージャー/講師 判定
        $deletableInstructorIds = $instructor->isManager()
            ? $instructor->managings->pluck('id')->push($instructor->id)->toArray()
            : [$instructor->id];

        foreach ($chapters as $chapter) {
            // 指定した講座に属するチャプターか判定
            if ($courseId !== $chapter->course_id) {
                return false;
            }

            // 認可確認
            if (!in_array($chapter->course->instructor_id, $deletableInstructorIds, true)) {
                return false;
            }
        }

        return true;
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
