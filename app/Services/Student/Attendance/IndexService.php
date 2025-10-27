<?php

namespace App\Services\Student\Attendance;

use App\Dto\Student\Attendance\IndexDto;
use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\Course;
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
            'course.chapters.lessons',
            'lessonAttendances',
            'course.tags',
            'course.courseDeadline',
        ])
            ->where('student_id', $indexDto->getStudentId())
            ->whereHas('course', function (Builder $query) use ($indexDto) {
                $query->when(! $indexDto->getSearchWord(), function (Builder $query) {
                    $query->where('status', Course::STATUS_PUBLIC);
                })->when($indexDto->getSearchWord(), function (Builder $query) use ($indexDto) {
                    $query->where('title', 'like', "%{$indexDto->getSearchWord()}%")
                        ->orWhereHas('tags', function (Builder $query) use ($indexDto) {
                            $query->where('content', 'like', "%{$indexDto->getSearchWord()}%");
                        })
                        ->where('status', Course::STATUS_PUBLIC);
                });
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
        return $attendance->course->chapters->filter(fn (Chapter $chapter) => $chapter->lessons->every(function (Lesson $lesson) use ($attendance) {
            $lessonAttendance = $attendance->lessonAttendances->firstWhere('lesson_id', $lesson->id);

            return $lessonAttendance && $lessonAttendance->status === LessonAttendance::STATUS_COMPLETED_ATTENDANCE;
        }))->count();
    }

    /**
     * チャプター合計を取得する
     */
    private function getTotalChaptersCount(Attendance $attendance): int
    {
        return $attendance->course->chapters->count();
    }
}
