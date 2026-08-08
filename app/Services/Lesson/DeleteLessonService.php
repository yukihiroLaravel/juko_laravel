<?php

namespace App\Services\Lesson;

use App\Model\Lesson;
use App\Model\LessonAttendance;
use Illuminate\Auth\Access\AuthorizationException;

class DeleteLessonService
{
    /**
     * @throws AuthorizationException
     */
    public function __invoke(Lesson $lesson): void
    {
        // 受講状況の存在チェック
        if (LessonAttendance::where('lesson_id', $lesson->id)->exists()) {
            throw new AuthorizationException('Forbidden, this lesson has attendance.');
        }

        $lesson->update(['order' => 0]);

        $lesson->delete();

        Lesson::where('chapter_id', $lesson->chapter_id)
            ->orderBy('order')
            ->get()
            ->each(fn (Lesson $lesson, int $index): bool => (bool) $lesson->update(['order' => $index + 1]));
    }
}
