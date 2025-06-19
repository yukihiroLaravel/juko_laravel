<?php

namespace App\Policies;

use App\Model\Instructor;
use App\Model\Lesson;
use Illuminate\Support\Collection;

class LessonPolicy
{
    /**
     * レッスン削除
     */
    public function delete(Instructor $instructor, Lesson $lesson): bool
    {
        // マネージャー権限のある講師か判定
        if ($instructor->isManager()) {
            $instructorIds = $instructor->managings->pluck('id')->toArray();
            $instructorIds[] = $instructor->id;

            return in_array($lesson->chapter->course->instructor_id, $instructorIds, true);
        }

        // マネージャー権限のない講師
        return $instructor->id === $lesson->chapter->course->instructor_id;
    }

    /**
     * 複数のレッスンを削除
     *
     * @param  Collection<int, Lesson>  $lessons
     */
    public function bulkDelete(Instructor $instructor, Collection $lessons): bool
    {
        if ($instructor->isManager()) {
            // 管理者の場合、配下の講師の講座も削除可能
            $managerIds = $instructor->managings->pluck('id')->toArray();
            $managerIds[] = $instructor->id;

            return $lessons->every(fn (Lesson $lesson) => in_array($lesson->chapter->course->instructor_id, $managerIds, true)
            );
        }

        // 講師の場合、自分の講座のみ削除可能
        return $lessons->every(fn (Lesson $lesson) => $lesson->chapter->course->instructor_id === $instructor->id
        );
    }
}
