<?php

namespace App\Policies;

use App\Model\Attendance;
use App\Model\Course;
use App\Model\Instructor;

class AttendancePolicy
{
    /**
     * Create a new policy instance.
     */
    public function delete(Instructor $instructor, Attendance $attendance): bool
    {
        // マネージャー権限のある講師か判定
        if ($instructor->isManager()) {
            $instructorIds = $instructor->managings->pluck('id')->toArray();
            $instructorIds[] = $instructor->id;

            return in_array($attendance->course->instructor_id, $instructorIds, true);
        }

        // マネージャー権限のない講師
        return $instructor->id === $attendance->course->instructor_id;
    }

    public function create(Instructor $instructor, Course $course): bool
    {
        // マネージャー権限のある講師か判定
        if ($instructor->isManager()) {
            $instructorIds = $instructor->managings->pluck('id')->toArray();
            $instructorIds[] = $instructor->id;

            return in_array($course->instructor_id, $instructorIds, true);
        }

        // マネージャー権限のない講師
        return $instructor->id === $course->instructor_id;
    }
}
