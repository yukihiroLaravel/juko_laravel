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
    private const MINIMUM_STUDENT_COUNT = 1;

    /**
     * 止まっている箇所を取得する
     *
     * @param int $courseId
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
        $stuckPoints = DB::table('lesson_attendances')
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

        $maxCount = $stuckPoints->max('count');

        // 受講済みのレッスンがない、または、受講済みの最多数が1人の場合は
        // 受講可能な最初の公開レッスンとチャプターを返す
        if ($stuckPoints->isEmpty() || (int) $maxCount === self::MINIMUM_STUDENT_COUNT) {
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
        $maxStuckPoints = $stuckPoints->where('count', $maxCount);
        $maxLastLesson = $maxStuckPoints->where('lesson_id', $lastLesson->id);
        $maxStuckLessons = $maxStuckPoints->where('lesson_id', '!=', $lastLesson->id);

        // 受講済み最多数のレッスンが最終レッスンのみの場合、空配列を返す
        if ($maxLastLesson->isNotEmpty() && $maxStuckLessons->isEmpty()) {
            return null;
        }

        // 最終レッスン以外の受講済み最多数のレッスンとチャプターを取得（ループ処理）
        $publicLessonsById = $publicLessons->keyBy('id');
        $publicChaptersById = $publicChapters->keyBy('id');
        return $maxStuckLessons
            ->map(function (array $maxStuckLesson) use ($publicLessonsById, $publicChaptersById) {
                $lesson = $publicLessonsById->get($maxStuckLesson['lesson_id']);
                $chapter = $publicChaptersById->get($lesson->chapter_id);
                return new StuckPointDto(
                    id: $chapter->id,
                    title: $chapter->title,
                    lessons: collect([
                        new StuckLessonDto(
                            id: $lesson->id,
                            title: $lesson->title,
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
