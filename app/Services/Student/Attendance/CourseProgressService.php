<?php

namespace App\Services\Student\Attendance;

use App\Dto\Student\Attendance\CourseProgressDto;
use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\Lesson;

class CourseProgressService
{
    /**
     * 受講している講座の進捗を集計する
     *
     * 下書き・非公開のチャプターとレッスンは受講生に表示されないため、
     * 分母・分子のいずれにも含めない。公開中のレッスンを持たないチャプターも
     * 到達しようがないため集計対象外とする。
     */
    public function __invoke(Attendance $attendance): CourseProgressDto
    {
        $chapters = $attendance->course->publicChapters
            ->filter(fn (Chapter $chapter) => $chapter->publicLessons->isNotEmpty());

        $lessons = $chapters->flatMap(fn (Chapter $chapter) => $chapter->publicLessons);

        return new CourseProgressDto(
            completedChaptersCount: $chapters
                ->filter(fn (Chapter $chapter) => $chapter->publicLessons
                    ->every(fn (Lesson $lesson) => $this->isCompleted($attendance, $lesson)))
                ->count(),
            totalChaptersCount: $chapters->count(),
            completedLessonsCount: $lessons
                ->filter(fn (Lesson $lesson) => $this->isCompleted($attendance, $lesson))
                ->count(),
            totalLessonsCount: $lessons->count(),
        );
    }

    /**
     * レッスンを完了済みか判定する
     */
    private function isCompleted(Attendance $attendance, Lesson $lesson): bool
    {
        return $attendance->lessonAttendances
            ->firstWhere('lesson_id', $lesson->id)
            ?->isCompleted() === true;
    }
}
