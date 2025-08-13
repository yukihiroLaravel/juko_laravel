<?php

namespace App\Policies;

use App\Model\Attendance;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Student;

class AttendancePolicy
{
    /**
     * 受講作成時のポリシー
     */
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

    /**
     * 受講状況削除時のポリシー
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

    /** Student: 進捗閲覧（progress） */
    public function view(Student $student, Attendance $attendance): bool
    {
        return $attendance->student_id === $student->id;        
    }

    /** Student: 更新（completeAllLessons / completeAllChapters） */
    public function update(Student $student, Attendance $attendance): bool
    {
        return $attendance->student_id === $student->id;
    }
}
