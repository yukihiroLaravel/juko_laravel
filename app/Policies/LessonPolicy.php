<?php

namespace App\Policies;

use App\Model\Instructor;
use App\Model\Lesson;
use Illuminate\Support\Collection;

class LessonPolicy
{
    /**
     * レッスンの更新処理に関する認可処理
     */
    public function update(Instructor $instructor, Lesson $lesson): bool
    {
        // マネージャーの場合は配下の講師のレッスンも更新可能
        if ($instructor->isManager()) {
            $manager = Instructor::with('managings')->find($instructor->id);
            $instructorIds = $manager->managings->pluck('id')->toArray();
            $instructorIds[] = $instructor->id;

            return in_array($lesson->chapter->course->instructor_id, $instructorIds, true);
        }

        // マネージャー権限のない講師の場合は自分のレッスンのみ更新可能
        return $lesson->chapter->course->instructor_id === $instructor->id;
    }

    /**
     * 複数レッスンの更新処理に関する認可処理
     * 
     */
    public function bulkUpdate(Instructor $instructor, Collection $lessons): bool
    {
        // マネージャーの場合は配下の講師のレッスンも更新可能
        if ($instructor->isManager()) {
            $manager = Instructor::with('managings')->find($instructor->id);
            $instructorIds = $manager->managings->pluck('id')->toArray();
            $instructorIds[] = $instructor->id;

            return $lessons->every(fn(Lesson $lesson) => in_array($lesson->chapter->course->instructor_id, $instructorIds, true));
        }

        // マネージャー権限のない講師の場合は自分のレッスンのみ更新可能
        return $lessons->every(fn (Lesson $lesson) => $lesson->chapter->course->instructor_id === $instructor->id);
    }

    public function delete(Instructor $instructor, Lesson $lesson): bool
    {
        // マネージャー権限のある講師か判定
        if ($instructor->isManager()) {
            $manager = Instructor::with('managings')->find($instructor->id);
            $instructorIds = $manager->managings->pluck('id')->toArray();
            $instructorIds[] = $instructor->id;

            return in_array($lesson->chapter->course->instructor_id, $instructorIds, true);
        }

        // マネージャー権限のない講師
        return $instructor->id === $lesson->chapter->course->instructor_id;
    }
}
