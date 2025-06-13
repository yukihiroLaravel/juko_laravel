<?php

namespace App\Policies;

use App\Model\Lesson;
use App\Model\Instructor;

class LessonPolicy
{
    /**
     * レッスンの更新処理に関する認可処理
     * 
     * @param Instructor $instructor
     * @param Lesson $lesson
     * 
     * @return bool
     */
    public function update(Instructor $instructor, Lesson $lesson): bool
    {
        // マネージャーの場合は配下の講師のレッスンも更新可能
        if ($instructor->isManager()) {
            $manager = Instructor::with('managings')->find($instructor->id);
            $instructorIds = $manager->managings->pluck('id')->toArray();
            $instructorIds[] = $instructor->id;

            return in_array($lesson->chapter->course->instructor_id, $instructorIds, true);
        }

        // マネージャー権限のない講師の場合は自分のレッスンのみ更新可能
        return $lesson->chapter->course->instructor_id === $instructor->id;
    }
}
