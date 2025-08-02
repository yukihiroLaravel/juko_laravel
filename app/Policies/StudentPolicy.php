<?php

namespace App\Policies;

use App\Model\Course;
use App\Model\Instructor;
use App\Model\Student;
use Illuminate\Support\Collection;

class StudentPolicy
{
    /**
     * 受講生取得認可ポリシー
     */
    public function view(Instructor $instructor, Student $student): bool
    {
        // 受講している講座のIDリストを取得
        $attendedCourseIds = $student->attendances->pluck('course_id')->unique();

        if ($instructor->isManager()) {
            $instructorIds = $instructor->managings->pluck('id');
            $instructorIds[] = $instructor->id;

            $courseIds = Course::whereIn('instructor_id', $instructorIds)->pluck('id');

            return $this->hasCommonCourses(attendedCourseIds: $attendedCourseIds, courseIds: $courseIds);
        }

        $courseIds = Course::where('instructor_id', $instructor->id)->pluck('id');

        return $this->hasCommonCourses(attendedCourseIds: $attendedCourseIds, courseIds: $courseIds);
    }

    /**
     * 受講済みの講座IDと、講師が担当する講座IDに共通するものがあるか確認
     *
     * @param  Collection<int>  $attendedCourseIds
     * @param  Collection<int>  $courseIds
     */
    private function hasCommonCourses(Collection $attendedCourseIds, Collection $courseIds): bool
    {
        return $attendedCourseIds->intersect($courseIds)->isNotEmpty();
    }
}
