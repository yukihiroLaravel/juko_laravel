<?php

namespace App\Policies;

use App\Model\Course;
use App\Model\Instructor;

class CoursePolicy
{
    /**
     * Determine whether the user can delete the model.
     */
    public function delete(Instructor $instructor, Course $course): bool
    {
        return $instructor->id === $course->instructor_id;
    }
}
