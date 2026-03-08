<?php

namespace App\Services\Student\Attendance;

use App\Model\Attendance;
use App\Model\LessonAttendance;
use App\Model\Chapter;
use Carbon\CarbonImmutable;

class StudentLearningHistoryService
{
    public function getLearningHistoryStats(int $studentId): array
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
                'courses' => $this->getCourseStats($studentId,$start,$end),
                'lessons' => $this->getLessonStats($studentId,$start,$end),
                'chapters' => $this->getChapterStats($studentId,$start,$end),
            ],
        ];
    }

        // 全コースが完了した数
        private function getCourseStats(int $studentId, CarbonImmutable $start, CarbonImmutable $end): array
        {
            $query = Attendance::where('student_id', $studentId);

            return [
                'completed' => (clone $query)->whereBetween('completed_at', [$start, $end])->count(),
                'total' => $query->count(),
            ];
        }
        
        // 全レッスンが完了した数
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
                    && $chapter->lessons_count === $chapter->completed_lessons_count)
                ->count();

            return [
                'completed' => $completed,
                'total' => $total,
            ];
    }
}