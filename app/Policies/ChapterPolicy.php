<?php

namespace App\Policies;

use App\Model\Instructor;
use Illuminate\Database\Eloquent\Collection;

class ChapterPolicy
{
    /**
     * 複数(選択)削除
     */
    public function bulkDelete(Instructor $instructor, Collection $chapters, int $courseId)
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

    public function deleteAll()
    {
        //
    }
}
