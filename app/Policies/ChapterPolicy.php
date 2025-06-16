<?php

namespace App\Policies;

use App\Model\Course;
use App\Model\Chapter;
use App\Model\Instructor;
use Illuminate\Support\Collection;


class ChapterPolicy
{
    public function update(Instructor $instructor, Chapter $chapter): bool
    {
    if ($instructor->isManager()) {
        // 管理者の場合、配下の講師のチャプターも更新可能
        $managerIds = $instructor->managings->pluck('id')->toArray();
        $managerIds[] = $instructor->id;

        return in_array($chapter->course->instructor_id, $managerIds, true);
    }

    // 講師の場合、自分のチャプターのみ更新可能
    return $instructor->id === $chapter->course->instructor_id;
    }

    /**
     * 一括削除認可処理
     */
    public function bulkDelete(Instructor $instructor, Collection $chapters): bool
    {
        if ($instructor->isManager()) {
            // 管理者の場合、配下の講師の講座も削除可能
            $managerIds = $instructor->managings->pluck('id')->toArray();
            $managerIds[] = $instructor->id;

            return $chapters->every(fn (Chapter $chapter) => in_array($chapter->course->instructor_id, $managerIds, true));
        }

        // 講師の場合、自分の講座のみ削除可能
        return $chapters->every(fn (Chapter $chapter) => $chapter->course->instructor_id === $instructor->id);
    }

    public function deleteAll(Instructor $instructor, Course $course): bool
    {
        if ($instructor->isManager()) {
            // 管理者の場合、配下の講師の講座も削除可能
            $managerIds = $instructor->managings->pluck('id')->toArray();
            $managerIds[] = $instructor->id;

            return in_array($course->instructor_id, $managerIds, true);
        }

        // 講師の場合、自分の講座のみ削除可能
        return $instructor->id === $course->instructor_id;
    }
}
