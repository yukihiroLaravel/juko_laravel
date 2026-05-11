<?php

namespace App\Services\Attendance;

use App\Dto\Instructor\Attendance\StuckLessonDto;
use App\Dto\Instructor\Attendance\StuckPointDto;
use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\Lesson;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use RuntimeException;

final class StuckPointsService
{
    private const int MINIMUM_REQUIRED_STUDENTS = 2;

    /**
     * 止まっている箇所を取得する
     *
     * @return Collection<StuckPointDto>
     */
    public function __invoke(int $courseId): Collection
    {

        $publicChapters = $this->getPublicChapters($courseId);
        $publicLessons = $this->getPublicLessons($publicChapters);
        $attendances = $this->getActiveAttendances($courseId);

        // 集計対象の講座、チャプター、レッスンがない場合は、空のコレクションを返す
        if (! $this->isEligible($publicChapters, $publicLessons, $attendances)) {
            return collect();
        }

        // 受講済みのレッスンを集計
        $completedLessonCounts = $this->countCompletedLessons($attendances, $publicLessons);

        // 受講済みの人数が最多のレッスンを取得
        $maxCount = $completedLessonCounts->max('lesson_attendances_count');
        
        // 最多数が必要受講生数未満の場合は、受講可能な最初の公開レッスンとチャプターを返す
        if ($maxCount < self::MINIMUM_REQUIRED_STUDENTS) {
            return $this->getFirstLesson($publicChapters, $publicLessons);
        }
        
        // 最多数のレッスンを取得
        $maxCompletedLessons = $completedLessonCounts
            ->where('lesson_attendances_count', $maxCount);

        // 最終レッスンの取得
        $lastLesson = $this->getLastLesson($publicChapters, $publicLessons);

        // 受講済みの最多数のレッスンの中で、最終レッスンの有無を確認
        $maxLastLesson = $maxCompletedLessons->where('id', $lastLesson->id);
        $maxCompletedLessons = $maxCompletedLessons->where('id', '!=', $lastLesson->id);

        // 受講済み最多数のレッスンが最終レッスンのみの場合、空配列を返す
        if ($maxLastLesson->isNotEmpty() && $maxCompletedLessons->isEmpty()) {
            return collect();
        }

        // 最終レッスン以外の受講済み最多数のレッスンの次の公開レッスンとチャプターを取得
        return $this->getNextLesson($maxCompletedLessons, $publicLessons, $publicChapters);
    }

    /**
     * 公開チャプターを取得
     *
     * @return Collection<Chapter>
     */
    private function getPublicChapters(int $courseId): Collection
    {
        return Chapter::where('course_id', $courseId)
            ->public()
            ->get();
    }

    /**
     * 公開レッスンを取得
     *
     * @param  Collection<Chapter>  $publicChapters
     * @return Collection<Lesson>
     */
    private function getPublicLessons(Collection $publicChapters): Collection
    {
        return Lesson::whereIn('chapter_id', $publicChapters->pluck('id'))
            ->public()
            ->get();
    }

    /**
     * 講座の受講期限が切れていない受講を取得
     *
     * @return Collection<Attendance>
     */
    private function getActiveAttendances(int $courseId): Collection
    {
        return Attendance::where('course_id', $courseId)
            ->where(function ($q) {
                $q->whereDate('attendance_deadline', '>=', CarbonImmutable::today())
                    ->orWhereNull('attendance_deadline'); // 無期限受講も含める
            })
            ->get();
    }

    /**
     * 集計対象として有効かを判定
     *
     * @param  Collection<Chapter>  $publicChapters
     * @param  Collection<Lesson>  $publicLessons
     * @param  Collection<Attendance>  $attendances
     */
    private function isEligible(Collection $publicChapters, Collection $publicLessons, Collection $attendances): bool
    {
        if ($publicChapters->isEmpty() || $publicLessons->isEmpty()) {
            return false;
        }

        return $attendances->pluck('student_id')
            ->unique()
            ->count() >= self::MINIMUM_REQUIRED_STUDENTS;
    }

    /**
     * 受講済みレッスン数を集計
     *
     * @param  Collection<Attendance>  $attendances
     * @param  Collection<Lesson>  $publicLessons
     * @return Collection<int, Lesson>
     */
    private function countCompletedLessons(Collection $attendances, Collection $publicLessons): Collection
    {
        return Lesson::query()
            ->whereIn('id', $publicLessons->pluck('id'))
            ->withCount(['lessonAttendances' => fn ($q) => $q
                ->whereIn('attendance_id', $attendances->pluck('id'))
                ->whereNotNull('completed_at'),
            ])
            ->get();
    }

    /**
     * 受講可能な最初の公開レッスンとチャプターを取得
     *
     * @param  Collection<Chapter>  $publicChapters
     * @param  Collection<Lesson>  $publicLessons
     * @return Collection<StuckPointDto>
     */
    private function getFirstLesson(Collection $publicChapters, Collection $publicLessons): Collection
    {
        $chapters = $publicChapters->sortBy('order');
        foreach ($chapters as $chapter) {
            $lesson = $publicLessons->where('chapter_id', $chapter->id)
                ->sortBy('order')
                ->first();
            if ($lesson) {
                return collect([
                    new StuckPointDto(
                        id: $chapter->id,
                        title: $chapter->title,
                        lessons: collect([
                            new StuckLessonDto(
                                id: $lesson->id,
                                title: $lesson->title,
                            ),
                        ]),
                    ),
                ]);
            }
        }

        // 到達しないはずだが、念のため例外を返す
        throw new RuntimeException('The first public lesson is not existed.');
    }

    /**
     * 受講可能な最終の公開レッスンを取得
     *
     * @param  Collection<Chapter>  $publicChapters
     * @param  Collection<Lesson>  $publicLessons
     */
    private function getLastLesson(Collection $publicChapters, Collection $publicLessons): Lesson
    {
        $chapters = $publicChapters->sortByDesc('order');
        foreach ($chapters as $chapter) {
            $lesson = $publicLessons->where('chapter_id', $chapter->id)->sortByDesc('order')->first();
            if ($lesson) {
                return $lesson;
            }
        }

        // 到達しないはずだが、念のため例外を返す
        throw new RuntimeException('The last public lesson is not existed.');
    }

    /**
     * 次に受講すべき公開レッスンとチャプターを取得
     *
     * @param  Collection<Lesson>  $maxCompletedLessons
     * @param  Collection<Lesson>  $publicLessons
     * @param  Collection<Chapter>  $publicChapters
     * @return Collection<StuckPointDto>
     */
    private function getNextLesson(Collection $maxCompletedLessons, Collection $publicLessons, Collection $publicChapters): Collection
    {
        return $maxCompletedLessons
            ->map(function (Lesson $maxCompletedLesson) use ($publicLessons, $publicChapters) {
                $lesson = $publicLessons->where('id', $maxCompletedLesson->id)->first();
                $stuckLesson = $publicLessons
                    ->where('chapter_id', $lesson->chapter_id)
                    ->where('order', '>', $lesson->order)
                    ->sortBy('order')
                    ->first();
                if ($stuckLesson) {
                    $stuckChapter = $publicChapters->where('id', $stuckLesson->chapter_id)->first();
                } else {
                    $chapter = $publicChapters->where('id', $lesson->chapter_id)->first();
                    $stuckChapter = $publicChapters
                        ->where('order', '>', $chapter->order)
                        ->sortBy('order')
                        ->first();
                    $stuckLesson = $publicLessons
                        ->where('chapter_id', $stuckChapter->id)
                        ->sortBy('order')
                        ->first();
                }

                return new StuckPointDto(
                    id: $stuckChapter->id,
                    title: $stuckChapter->title,
                    lessons: collect([
                        new StuckLessonDto(
                            id: $stuckLesson->id,
                            title: $stuckLesson->title,
                        ),
                    ]),
                );
            })
            ->pipe(fn (Collection $stuckPoints) => $this->mergeDuplicatedChapters($stuckPoints));
    }

    /**
     * 重複するチャプターをまとめる
     *
     * @param  Collection<StuckPointDto>  $stuckPoints
     * @return Collection<StuckPointDto>
     */
    private function mergeDuplicatedChapters(Collection $stuckPoints): Collection
    {
        return $stuckPoints
            ->groupBy('id')
            ->map(function ($items) {
                $first = $items->first();

                return new StuckPointDto(
                    id: $first->id,
                    title: $first->title,
                    lessons: $items->flatMap(fn ($i) => $i->lessons),
                );
            })
            ->values();
    }
}
