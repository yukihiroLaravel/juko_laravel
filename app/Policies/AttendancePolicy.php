<?php

namespace App\Policies;

use App\Model\Instructor;
use App\Model\Course;
use App\Model\Attendance;

class AttendancePolicy
{
    /**
     * Determine whether the user can delete the model.
     */
    public function deletable(Instructor $instructor, Course $course): bool
    {
        return !Attendance::where('course_id', $course->course_id)->exists();
    }
}
