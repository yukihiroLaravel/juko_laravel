<?php

namespace App\Services\Student\Attendance;

use App\Dto\Student\Attendance\IndexDto;
use App\Enums\Course\StatusEnum as CourseStatusEnum;
use App\Enums\Chapter\StatusEnum as ChapterStatusEnum;
use App\Enums\Lesson\StatusEnum as LessonStatusEnum;
use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\Lesson;
use App\Model\LessonAttendance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class IndexService
{
    /**
     * @return LengthAwarePaginator<Attendance>
     */
    public function __invoke(
        IndexDto $indexDto, int $perPage, int $page, ?int $tagId
    ): LengthAwarePaginator {
        // 受講情報を関連情報と一緒に取得
        $attendances = Attendance::with([
            'course.instructor',
            'course.chapters' => fn ($q) => $q->where('status', ChapterStatusEnum::PUBLIC->value),
            'course.chapters.lessons' => fn ($q) => $q->where('status', LessonStatusEnum::PUBLIC->value),
            'lessonAttendances',
            'course.tags',
            'course.courseDeadline',
        ])
            ->where('student_id', $indexDto->getStudentId())
            ->whereHas('course', function (Builder $query) use ($indexDto) {
                $query->where('status', CourseStatusEnum::PUBLIC->value);
                if ($indexDto->getSearchWord()) {
                    $word = $indexDto->getSearchWord();
                    $query->where(function (Builder $q) use ($word) {
                        $q->where('title', 'like', "%{$word}%")
                            ->orWhereHas('tags', fn ($tagQuery) => $tagQuery->where('content', 'like', "%{$word}%"));
                    });
                }
            })
            ->when($tagId, function (Builder $query) use ($tagId) {
                $query->whereHas('course.tags', function (Builder $query) use ($tagId) {
                    $query->where('tags.id', $tagId);
                });
            })
            ->paginate($perPage, ['*'], 'page', $page);

        // 各受講情報ごとにチャプター単位の進捗率を計算
        $attendances->each(function (Attendance $attendance) {
            $completedChaptersCount = $this->getCompletedChaptersCount($attendance);
            $totalChaptersCount = $this->getTotalChaptersCount($attendance);
            $progressPercentage = ($totalChaptersCount > 0) ? round(($completedChaptersCount / $totalChaptersCount) * 100) : 0;
            $attendance->course->progress_percentage = $progressPercentage;
        });

        return $attendances;
    }

    /**
     * 完了済みのチャプター数を取得する
     */
    private function getCompletedChaptersCount(Attendance $attendance): int
    {
        return $attendance->course->chapters->filter(fn (Chapter $chapter) =>
            $chapter->status === ChapterStatusEnum::PUBLIC &&
            $chapter->lessons->filter(
                fn (Lesson $lesson) => $lesson->status === LessonStatusEnum::PUBLIC
            )
            ->every(function (Lesson $lesson) use ($attendance) {
                $lessonAttendance = $attendance->lessonAttendances->firstWhere('lesson_id', $lesson->id);

            return $lessonAttendance && $lessonAttendance->status === LessonAttendance::STATUS_COMPLETED_ATTENDANCE;
        }))->count();
    }

    /**
     * チャプター合計を取得する
     */
    private function getTotalChaptersCount(Attendance $attendance): int
    {
        return $attendance->course->chapters
            ->filter(fn (Chapter $chapter) =>
                    $chapter->status === ChapterStatusEnum::PUBLIC
            )
            ->count();
    }
}
