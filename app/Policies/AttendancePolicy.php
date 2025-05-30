<?php

namespace App\Policies;

use App\Model\Attendance;
use App\Model\Course;

class AttendancePolicy
{
    /**
     * Determine whether the user can delete the model.
     */
    public function delete(Course $course): bool
    {
        return Attendance::where('course_id', $course->course_id)->exists();
    }
}
