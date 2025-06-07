<?php

namespace App\Policies;

use App\Model\Instructor;
use App\Model\Lesson;

class LessonPolicy
{
    public function delete(Instructor $instructor, Lesson $lesson): bool
    {
        // マネージャー権限のある講師か判定
        if ($instructor->isManager()) {
            $manager = Instructor::with('managings')->find($instructor->id);
            $instructorIds = $manager->managings->pluck('id')->toArray();
            $instructorIds[] = $instructor->id;

            return in_array($lesson->chapter->course->instructor_id, $instructorIds, true);
        }

        // マネージャー権限のない講師
        return $instructor->id === $lesson->chapter->course->instructor_id;
    }
}
