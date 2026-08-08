<?php

namespace App\Services\Attendance;

use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\Course;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class ShowService
{
    /**
     * 受講状況詳細に必要な情報（期限切れを除いた受講人数を含む）を取得する
     *
     * @return array{
     *     studentsCount: int<0, max>,
     *     chapters: Collection<int, array{
     *         chapter: Chapter,
     *         completedStudentsCount: int<0, max>
     *     }>
     * }
     */
    public function __invoke(Course $course): array
    {
        $course->loadMissing(['chapters.lessons']);

        $validAttendances = Attendance::with('lessonAttendances')
            ->where('course_id', $course->id)
            ->where(function ($query) {
                $query->whereNull('attendance_deadline')
                    ->orWhere('attendance_deadline', '>=', CarbonImmutable::today());
            })
            ->get();

        $studentsCount = $validAttendances->count();

        $chapters = $course->chapters->map(function (Chapter $chapter) use ($validAttendances) {
            $allLessonIds = $chapter->lessons->pluck('id');
            $totalLessonsCount = $allLessonIds->count();

            if ($totalLessonsCount === 0) {
                return [
                    'chapter' => $chapter,
                    'completedStudentsCount' => 0,
                ];
            }

            $completedStudentsCount = $validAttendances->filter(function (Attendance $attendance) use ($allLessonIds, $totalLessonsCount) {
                $completedLessonsCount = $attendance->lessonAttendances
                    ->whereIn('lesson_id', $allLessonIds)
                    ->whereNotNull('completed_at')
                    ->count();

                return $totalLessonsCount === $completedLessonsCount;
            })->count();

            return [
                'chapter' => $chapter,
                'completedStudentsCount' => $completedStudentsCount,
            ];
        });

        return [
            'studentsCount' => $studentsCount,
            'chapters' => $chapters,
        ];
    }
}
