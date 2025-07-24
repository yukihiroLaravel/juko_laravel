<?php

namespace App\Services\Lesson;

use App\Model\Attendance;
use App\Model\Lesson;
use App\Model\LessonAttendance;

class StoreLessonService
{
    public function __invoke(
        int $courseId,
        int $chapterId,
        string $title,
        string $status
    ): Lesson {
        $nextOrder = Lesson::where('chapter_id', $chapterId)->max('order') + 1;

        $lesson = Lesson::create([
            'chapter_id' => $chapterId,
            'title' => $title,
            'status' => $status,
            'order' => $nextOrder,
        ]);

        $attendances = Attendance::where('course_id', $courseId)->get();
        foreach ($attendances as $attendance) {
            LessonAttendance::create([
                'attendance_id' => $attendance->id,
                'lesson_id' => $lesson->id,
                'status' => LessonAttendance::STATUS_BEFORE_ATTENDANCE,
            ]);
        }

        return $lesson;
    }
}
