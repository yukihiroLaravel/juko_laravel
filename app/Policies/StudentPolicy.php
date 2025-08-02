<?php

namespace App\Policies;

use App\Model\Instructor;
use App\Model\Student;
use App\Model\Course;

class StudentPolicy
{
    /**
     * 受講生詳細取得
     */
    public function view(Instructor $instructor, Student $student): bool
    {
        if ($instructor->isManager()) {
            $instructorIds = $instructor->managings->pluck('id')->toArray();
            $instructorIds[] = $instructor->id;

            $studentCourseIds = $student->attendances->pluck('course_id')->unique();
            $courseIds = Course::whereIn('instructor_id', $instructorIds)->pluck('id');

            return $studentCourseIds->intersect($courseIds)->isNotEmpty();
        }

        $courseIds = Course::where('instructor_id', $instructor->id)->pluck('id');
        $studentCourseIds = $student->attendances->pluck('course_id')->unique();

        return $studentCourseIds->intersect($courseIds)->isNotEmpty();
    }
}
