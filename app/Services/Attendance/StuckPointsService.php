<?php

namespace App\Services\Attendance;

use App\Dto\Instructor\Attendance\StuckLessonDto;
use App\Dto\Instructor\Attendance\StuckPointDto;
use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\CourseDeadline;
use App\Model\Lesson;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class StuckPointsService
{
    private const int MINIMUM_STUDENT_COUNT = 1;

    /**
     * 止まっている箇所を取得する
     *
     * @return Collection<StuckPointDto>|null
     */
    public function __invoke(int $courseId): ?Collection
    {
        // 当該講座の受講期限を確認
        $hasCourseDeadline = CourseDeadline::where('course_id', $courseId)
            ->whereDate('fixed_date', '>=', CarbonImmutable::today())
            ->exists();
        // 受講期限が切れている場合は空配列を返す
        if (! $hasCourseDeadline) {
            return null;
        }

        // 公開チャプターを取得
        $publicChapters = Chapter::where('course_id', $courseId)
            ->where('status', Chapter::STATUS_PUBLIC)
            ->get();
        // 公開チャプターが1件も存在しない場合は空配列を返す
        if ($publicChapters->isEmpty()) {
            return null;
        }

        // 公開レッスンを取得
        $publicLessons = Lesson::whereIn('chapter_id', $publicChapters->pluck('id'))
            ->where('status', Lesson::STATUS_PUBLIC)
            ->get();
        // 公開レッスンが1件も存在しない場合は空配列を返す
        if ($publicLessons->isEmpty()) {
            return null;
        }

        // 講座の受講期限が切れていない受講を取得
        $attendances = Attendance::where('course_id', $courseId)
            ->whereDate('attendance_deadline', '>=', CarbonImmutable::today())
            ->get();
        // 受講生が1人の場合は空配列を返す
        if ($attendances->pluck('student_id')->unique()
                ->count() <= self::MINIMUM_STUDENT_COUNT) {
            return null;
        }

        // 受講済みのレッスンを集計
        $completedLessonCounts = DB::table('lesson_attendances')
            ->selectRaw('lesson_id, COUNT(*) as count')
            ->whereIn('attendance_id', $attendances->pluck('id'))
            ->whereIn('lesson_id', $publicLessons->pluck('id'))
            ->whereNotNull('completed_at')
            ->whereNull('deleted_at')
            ->groupBy('lesson_id')
            ->get()
            ->map(fn ($stuckPoint) => [
                'lesson_id' => (int) $stuckPoint->lesson_id,
                'count' => (int) $stuckPoint->count,
            ]);

        $maxCount = $completedLessonCounts->max('count');

        // 受講済みのレッスンがない、または、受講済みの最多数が1人の場合は
        // 受講可能な最初の公開レッスンとチャプターを返す
        if ($completedLessonCounts->isEmpty() || (int) $maxCount === self::MINIMUM_STUDENT_COUNT) {
            $chapters = $publicChapters->sortBy('order');
            foreach ($chapters as $chapter) {
                $lesson = $publicLessons->where('chapter_id', $chapter->id)->sortBy('order')->first();
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
        }

        // 最終レッスンの取得
        $chapters = $publicChapters->sortByDesc('order');
        $lastLesson = null;
        foreach ($chapters as $chapter) {
            $lesson = $publicLessons->where('chapter_id', $chapter->id)->sortByDesc('order')->first();
            if ($lesson) {
                $lastLesson = $lesson;
                break;
            }
        }

        // 受講済みの最多数のレッスンを取得し、最終レッスンの有無を確認
        $maxCompletedLessonCounts = $completedLessonCounts->where('count', $maxCount);
        $maxLastLesson = $maxCompletedLessonCounts->where('lesson_id', $lastLesson->id);
        $maxCompletedLessons = $maxCompletedLessonCounts->where('lesson_id', '!=', $lastLesson->id);

        // 受講済み最多数のレッスンが最終レッスンのみの場合、空配列を返す
        if ($maxLastLesson->isNotEmpty() && $maxCompletedLessons->isEmpty()) {
            return null;
        }

        // 最終レッスン以外の受講済み最多数のレッスンの次の公開レッスンとチャプターを取得
        return $maxCompletedLessons
            ->map(function (array $maxCompletedLesson) use ($publicLessons, $publicChapters,) {
                $lesson = $publicLessons->where('id', $maxCompletedLesson['lesson_id'])->first();
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
            // 重複チャプターをまとめる
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
