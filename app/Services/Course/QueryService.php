<?php

namespace App\Services\Course;

use App\Model\Course;
use Illuminate\Database\Eloquent\Collection;

class QueryService
{
    /**
     * 選択されたコースを取得
     *
     * @param int $courseId
     * @return Course
     */
    public function getCourse(int $courseId): Course
    {
        return Course::with(['chapters.lessons'])->findOrFail($courseId);
    }

    /**
     * 選択されたコースリストを取得
     *
     * @param array<int> $courseIds
     * @return Collection<Course>
     */
    public function getCourses(int $instructorId): Collection
    {
        return Course::where('instructor_id', $instructorId)->get();
    }
}
