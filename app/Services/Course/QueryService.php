<?php

namespace App\Services\Course;
use App\Model\Attendance;
use App\Model\Course;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class QueryService
{
    /**
     * 講座情報を取得
     */
    public function getCourse(int $courseId): Course
    {
        return Course::with(['chapters.lessons'])->findOrFail($courseId);
    }

    /**
     * 講師IDから講座情報を取得
     *
     * @return Collection<Course>
     */
    public function getCoursesByInstructorId(int $instructorId): Collection
    {
        return Course::where('instructor_id', $instructorId)->get();
    }

    /**
     * 講師IDのリストから講座情報を取得
     *
     * @param  array<int>  $instructorIds
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
     */
    public function getPaginatedCoursesByInstructorId(int $instructorId, int $perPage): LengthAwarePaginator
    {
        return Course::where('instructor_id', $instructorId)->paginate($perPage);
    }

    /**
     * 講師IDから講座情報を取得（受講中の生徒の有無を含む）
     *
     * @param int $instructorId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPaginatedCoursesWithActiveStudents(int $instructorId, int $perPage): LengthAwarePaginator
    {
        // ページネーションで講座を取得
        $courses = Course::where('instructor_id', $instructorId)->paginate($perPage);

        // 各講座に受講中の生徒がいるかを判定し、情報を付加
        $courses->getCollection()->transform(function (Course $course) {
            $course->has_active_students = Attendance::where('course_id', $course->id)
                ->where('progress', '>', 0) // 進捗がある場合
                ->exists();
            return $course;
        });

        return $courses;
    }
}
