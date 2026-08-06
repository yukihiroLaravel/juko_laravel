<?php

namespace App\Services\Student\Attendance;

use App\Dto\Student\Attendance\IndexDto;
use App\Enums\Course\StatusEnum as CourseStatusEnum;
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
            'course.publicChapters',
            'course.publicChapters.publicLessons',
            'lessonAttendances',
            'course.tags',
            'course.courseDeadline',
        ])
            ->where('student_id', $indexDto->getStudentId())
            ->whereHas('course', function (Builder $query) use ($indexDto) {
                $searchWord = $indexDto->getSearchWord();
                $query->where('status', CourseStatusEnum::PUBLIC->value)
                    ->when($searchWord !== null && $searchWord !=='', function (Builder $query) use ($searchWord) {
                        $query->where(function (Builder $query) use ($searchWord) {
                            $query->where('title', 'like', "%{$searchWord}%")
                                ->orWhereHas('tags', fn (Builder $query) => $query->where('content', 'like', "%{$searchWord}%"));
                        });
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
     * 完了済み・公開中のチャプター数を取得する
     */
    private function getCompletedChaptersCount(Attendance $attendance): int
    {
        return $attendance->course->publicChapters->filter(function (Chapter $chapter) use ($attendance) {

            // 公開レッスンがないチャプターは対象外
            if ($chapter->publicLessons->isEmpty()) {
                return false;
            }

            // 公開レッスンのみで every() を判定する
            return $chapter->publicLessons->every(function (Lesson $lesson) use ($attendance) {
                $lessonAttendance = $attendance->lessonAttendances->firstWhere('lesson_id', $lesson->id);

                return $lessonAttendance &&
                    $lessonAttendance->status === LessonAttendance::STATUS_COMPLETED_ATTENDANCE;
            });
        })->count();
    }

    /**
     * 公開中のチャプター合計を取得する
     */
    private function getTotalChaptersCount(Attendance $attendance): int
    {
        return $attendance->course->publicChapters
            ->filter(fn (Chapter $chapter) => $chapter->publicLessons->isNotEmpty())
            ->count();
    }
}
