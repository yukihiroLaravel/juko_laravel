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
     * 下書き・非公開のチャプターとレッスンは受講生に表示されないため、件数に含めない。
     * 公開中のレッスンを1つも持たないチャプターは受講しようがないため集計対象から外す。
     */
    public function __invoke(Attendance $attendance): CourseProgressDto
    {
        $attendance->loadMissing(['course.publicChapters.publicLessons', 'lessonAttendances']);

        $chapters = $attendance->course->publicChapters
            ->filter(fn (Chapter $chapter) => $chapter->publicLessons->isNotEmpty());

        $lessons = $chapters->flatMap(fn (Chapter $chapter) => $chapter->publicLessons);

        return new CourseProgressDto(
            completedChaptersCount: $chapters
                ->filter(fn (Chapter $chapter) => $chapter->publicLessons
                    ->every(fn (Lesson $lesson) => $attendance->hasCompletedLesson($lesson)))
                ->count(),
            totalChaptersCount: $chapters->count(),
            completedLessonsCount: $lessons
                ->filter(fn (Lesson $lesson) => $attendance->hasCompletedLesson($lesson))
                ->count(),
            totalLessonsCount: $lessons->count(),
        );
    }
}
