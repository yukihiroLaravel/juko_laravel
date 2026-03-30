<?php

namespace App\Services\Student\Attendance;

use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\LessonAttendance;
use App\Model\StudentLoginHistory;
use Carbon\CarbonImmutable;

class StudentLearningHistoryService
{
    public function __invoke(int $studentId): array
    {
        $end = CarbonImmutable::now();
        $start = $end->subDays(30);

        return [
            'window' => [
                'type' => 'last_30_days',
                'start' => $start->format('Y-m-d'),
                'end' => $end->format('Y-m-d'),
            ],
            'stats' => [
                'courses' => $this->getCourseStats($studentId, $start, $end),
                'lessons' => $this->getLessonStats($studentId, $start, $end),
                'chapters' => $this->getChapterStats($studentId, $start, $end),
                'login_count' => $this->getLoginCount($studentId, $start, $end),
            ],
        ];
    }

    /**
     * 30日以内に完了した講座数
     */
    private function getCourseStats(int $studentId, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $query = Attendance::where('student_id', $studentId);

        return [
            'completed' => (clone $query)->whereBetween('completed_at', [$start, $end])->count(),
            'total' => $query->count(),
        ];
    }

    /**
     * 30日以内のログイン回数
     */
    private function getLoginCount(int $studentId, CarbonImmutable $start, CarbonImmutable $end): int
    {
        return StudentLoginHistory::where('student_id', $studentId)
            ->whereBetween('logged_in_at', [$start, $end])
            ->count();
    }

    /**
     * 30日以内に完了したレッスン数
     */
    private function getLessonStats(int $studentId, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $query = LessonAttendance::whereHas('attendance', function ($q) use ($studentId) {
            $q->where('student_id', $studentId);
        });

        return [
            'completed' => (clone $query)->whereBetween('completed_at', [$start, $end])->count(),
            'total' => $query->count(),
        ];
    }

    /**
     * 30日以内に完了したチャプター数
     */
    private function getChapterStats(int $studentId, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $baseQuery = Chapter::whereHas('course.attendances', function ($q) use ($studentId) {
            $q->where('student_id', $studentId);
        });

        $total = (clone $baseQuery)->count();

        // チャプター内の全レッスンが期間内に完了しているチャプターを数える
        $completed = (clone $baseQuery)
            ->withCount([
                'lessons',
                'lessons as completed_lessons_count' => function ($q) use ($studentId, $start, $end) {
                    $q->whereHas('lessonAttendances', function ($q2) use ($studentId, $start, $end) {
                        $q2->whereHas('attendance', function ($q3) use ($studentId) {
                            $q3->where('student_id', $studentId);
                        })->whereBetween('completed_at', [$start, $end]);
                    });
                },
            ])
            ->get()
            ->filter(fn (Chapter $chapter) => $chapter->lessons_count > 0
                && $chapter->lessons_count === (int) $chapter->getAttribute('completed_lessons_count'))
            ->count();

        return [
            'completed' => $completed,
            'total' => $total,
        ];
    }
}
