<?php

namespace App\Services\Course;

use App\Model\Course;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class QueryService
{
    /**
     * 講座情報を取得
     *
     * @param int $courseId
     * @return Course
     */
    public function getCourse(int $courseId): Course
    {
        return Course::with(['chapters.lessons'])->findOrFail($courseId);
    }

    /**
     * 講師IDから講座情報を取得
     *
     * @param int $instructorId
     * @return Collection<Course>
     */
    public function getCoursesByInstructorId(int $instructorId): Collection
    {
        return Course::where('instructor_id', $instructorId)->get();
    }

    /**
     * 講師IDのリストから講座情報を取得
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
     * 講師IDから講座情報を取得（ページネーション）
     *
     * @param int $instructorId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPaginatedCoursesByInstructorId(int $instructorId, int $perPage): LengthAwarePaginator
    {
        return Course::where('instructor_id', $instructorId)->paginate($perPage);
    }
}
