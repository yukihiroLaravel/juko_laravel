<?php

namespace App\Policies;

use App\Model\Course;
use App\Model\Instructor;

class CoursePolicy
{
    /**
     * Determine whether the instructor can delete the course.
     */
    public function delete(Instructor $instructor, Course $course): bool
    {
        // マネージャー権限のある講師か判定
        if ($instructor->isManager()) {
            $manager = Instructor::with('managings')->find($instructor->id);
            $instructorIds = $manager->managings->pluck('id')->toArray();
            $instructorIds[] = $instructor->id;

            return in_array($course->instructor_id, $instructorIds, true);
        }

        // マネージャー権限のない講師
        return $instructor->id === $course->instructor_id;
    }
}
