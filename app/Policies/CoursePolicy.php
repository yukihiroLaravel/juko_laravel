<?php

namespace App\Policies;

use App\Model\Instructor;
use App\Model\Course;
use App\Model\Attendance;
use Illuminate\Auth\Access\Response;

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
