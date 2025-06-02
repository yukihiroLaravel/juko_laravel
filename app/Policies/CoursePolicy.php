<?php

namespace App\Policies;

use App\Model\Attendance;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\ManageInstructor;

class CoursePolicy
{
    /**
     * Determine whether the instructor can delete the course.
     */
    public function delete(Instructor $instructor, Course $course): bool
    {
        // マネージャー権限のある講師か判定
        isManager($instructor);

        // マネージャー権限のない講師
        return $instructor->id === $course->instructor_id;
    }

    public function deletable(Instructor $instructor, Course $course): bool
    {
        return ! Attendance::where('course_id', $course->id)->exists();
    }
}
