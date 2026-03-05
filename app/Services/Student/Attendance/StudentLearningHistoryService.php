<?php

namespace App\Services\Student\Attendance;

use App\Model\Attendance;
use App\Model\LessonAttendance;
use App\Model\Chapter;

class StudentLearningHistoryService
{
    public function getStatus(int $studentId): array
    {
        $now = now();
        $start = $now->copy()->subDays(30);
        $end = $now;

        // 講座の全数
        $totalCourses = Attendance::where('student_id', $studentId)->count();

        // 30日以内に完了した講座
        $completedCourses = Attendance::where('student_id', $studentId)
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$start, $end])
            ->count();

        // 全レッスン数
        $totalLessons = LessonAttendance::whereHas('attendance', function ($query) use ($studentId) {
                $query->where('student_id', $studentId);
            })
            ->count();

        // 30日以内に完了したレッスン数
        $completedLessons = LessonAttendance::whereHas('attendance', function ($query) use ($studentId) {
                $query->where('student_id', $studentId);
            })
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$start, $end])
            ->count();

        // 全チャプター数
        $totalChapters = Chapter::whereHas('course.attendances', function ($query) use ($studentId) {
            $query->where('student_id', $studentId);            
        })
        ->count();

        // 30日以内に完了したチャプター数
        $completedChapters = Chapter::whereHas('course.attendances', function ($query) use ($studentId) {
            $query->where('student_id', $studentId);
        })
        ->whereHas('lessons.lessonAttendances', function ($query) use ($studentId) {
            $query->whereHas('attendance', function ($q) use ($studentId) {
                $q->where('student_id', $studentId);
            });
        })
        ->whereDoesntHave('lessons.lessonAttendances', function ($query) use ($studentId, $start, $end) {
            $query->whereHas('attendance', function ($q) use ($studentId) {
                $q->where('student_id', $studentId);
            })
            ->where(function ($q) use ($start, $end) {
                $q->whereNull('completed_at')
                  ->orWhereNotBetween('completed_at', [$start, $end]);
            });
        })
        ->count();

        return [
            'window' => [
                'type' => 'last_30_days',
                'start' => $start->format('Y-m-d'),
                'end' => $end->format('Y-m-d'),
            ],
            'stats' => [
                'courses' => [
                    'completed' => $completedCourses,
                    'total' => $totalCourses,
                ],
                'lessons' => [
                    'completed' => $completedLessons,
                    'total' => $totalLessons,
                ],
                'chapters' => [
                    'completed' => $completedChapters,
                    'total' => $totalChapters,
                ],
            ],
        ];
    }
}