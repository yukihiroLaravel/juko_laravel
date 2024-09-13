<?php

namespace App\Services\Course;

use App\Model\Course;
use Illuminate\Database\Eloquent\Collection;

class QueryService
{
    /**
     * 選択された講座の情報を取得
     *
     * @param int $courseId
     * @return Course
     */
    public function getCourse(int $courseId): Course
    {
        return Course::with(['chapters.lessons'])->findOrFail($courseId);
    }

    /**
     * 選択された講師の講座情報を取得
     *
     * @param int $instructorId
     * @return Collection<Course>
     */
    public function getCoursesByInstructorId(int $instructorId): Collection
    {
        return Course::where('instructor_id', $instructorId)->get();
    }
}
