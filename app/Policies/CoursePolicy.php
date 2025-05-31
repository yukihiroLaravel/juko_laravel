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
        if (ManageInstructor::where('manager_id', $instructor->id)->exists()) {
            // マネージャー権限のある講師
            $manager = Instructor::with('managings')->find($instructor->id);
            if (! $manager) {
                return false;
            }
            $instructorIds = $manager->managings->pluck('id')->toArray();
            $instructorIds[] = $instructor->id;

            return in_array($course->instructor_id, $instructorIds);

        } else {
            // マネージャー権限のない講師
            return $instructor->id === $course->instructor_id;
        }
    }

    public function deletable(Instructor $instructor, Course $course): bool
    {
        return ! Attendance::where('course_id', $course->id)->exists();
    }
}
