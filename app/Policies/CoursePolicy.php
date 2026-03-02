<?php

namespace App\Policies;

use App\Model\Course;
use App\Model\Instructor;
use Illuminate\Support\Collection;

class CoursePolicy
{
    public function view(Instructor $instructor, Course $course): bool
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

    public function update(Instructor $instructor, Course $course): bool
    {
        if ($instructor->isManager()) {
            // 管理者の場合、配下の講師の講座も更新可能
            $managerIds = $instructor->managings->pluck('id')->toArray();
            $managerIds[] = $instructor->id;

            return in_array($course->instructor_id, $managerIds, true);
        }

        // 講師の場合、自分の講座のみ更新可能
        return $instructor->id === $course->instructor_id;
    }

    public function delete(Instructor $instructor, Course $course): bool
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
     * 複数講座のステータス更新に関する認可処理
     *
     * @param  Collection<int, Course>  $courses
     */
    public function bulkUpdate(Instructor $instructor, Collection $courses): bool
    {
        // マネージャー権限あり
        if ($instructor->isManager()) {
            $managerIds = $instructor->managings->pluck('id')->push($instructor->id);

            return $courses->every(
                fn (Course $course) => $managerIds->contains($course->instructor_id)
            );
        }

        // 一般講師
        return $courses->every(fn (Course $course) => $course->instructor_id === $instructor->id);
    }

    /**
     * 講座一括削除に関する認可処理
     *
     * @param  Collection<int, Course>  $courses
     */
    public function bulkDelete(Instructor $instructor, Collection $courses): bool
    {
        // マネージャー権限あり
        if ($instructor->isManager()) {
            $managerIds = $instructor->managings->pluck('id')->toArray();
            $managerIds[] = $instructor->id;

            return $courses->every(fn (Course $course) => in_array($course->instructor_id, $managerIds, true));
        }

        // 一般講師
        return $courses->every(fn (Course $course) => $course->instructor_id === $instructor->id);
    }
}
