<?php

namespace App\Policies;

use App\Model\Course;
use App\Model\Instructor;

class CoursePolicy
{
    /**
     * Determine whether the user can update the model.
     */
    public function managerPolicy(Instructor $instructor, Course $course): bool
    {
        $manager = Instructor::with('managings')->find($instructor->id);

        if (! $manager) {
            return false;
        }

        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $instructor->id;

        return in_array($course->instructor_id, $instructorIds);
    }

    public function instructorPolicy(Instructor $instructor, Course $course): bool
    {
        // 講師が保有する講座なら処理可能
        return $instructor->id === $course->instructor_id;
    }
}
