<?php

namespace App\Services\Course;

use App\Model\Course;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

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
     * ログイン中の講師の所有するコースリストを取得
     *
     * @param int $instructorId
     * @return Collection<Course>
     */
    public function getCoursesByInstructorId(int $instructorId): Collection
    {
        return Course::where('instructor_id', $instructorId)->get();
    }

    /**
     * ログイン中のマネージャーの所有するコースリストと配下の講師の所有するコースリストを取得
     *
     * @param array<int> $instructorIds
     * @return Collection<Course>
     */
    public function getCoursesByInstructorIds(array $instructorIds): Collection
    {
        return Course::with('instructor')
        ->whereIn('instructor_id', $instructorIds)
        ->get();
    }

    /**
     * ログイン中のマネージャー及び配下の講師の所有するコースリストを個別指定して取得
     *
     * @param int $instructorId
     * @return LengthAwarePaginator
     */
    public function getCoursesByManagerInstructorId(int $instructorId): LengthAwarePaginator
    {
        return Course::where('instructor_id', $instructorId)->paginate(5);
    }
}
