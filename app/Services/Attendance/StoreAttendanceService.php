<?php

namespace App\Services\Attendance;

use App\Model\Attendance;
use App\Model\Course;
use App\Model\Lesson;
use App\Model\LessonAttendance;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StoreAttendanceService
{
    public function __invoke(
        int $courseId,
        int $studentId,
        CalculateDeadlineService $calculateDeadline
    ): void {
        DB::transaction(function () use ($courseId, $studentId, $calculateDeadline) {

            // 講座取得
            $course = Course::where('id', $courseId)
                ->lockForUpdate()
                ->firstOrFail();

            // 講座定員チェック
            if (! $course->hasCapacity()) {
                throw ValidationException::withMessages([
                    'course_id' => 'The course is already full.',
                ]);
            }

            if (Attendance::where('course_id', $course->id)
                ->where('student_id', $studentId)
                ->exists()
            ) {
                throw ValidationException::withMessages([
                    'student_id' => 'Attendance record already exists.',
                ]);
            }

            $deadlineSetting = $course->courseDeadline;
            $deadlineType = $course->deadline_type;

            $attendanceDeadline = $calculateDeadline(
                deadlineType: $deadlineType,
                startAt: new CarbonImmutable,
                fixedDate: $deadlineSetting?->fixed_date ? new CarbonImmutable($deadlineSetting->fixed_date) : null,
                relativeDays: $deadlineSetting?->relative_days,
            );

            $attendance = Attendance::create([
                'course_id' => $course->id,
                'student_id' => $studentId,
                'attendance_deadline' => $attendanceDeadline,
            ]);

            $lessons = Lesson::whereHas('chapter', function ($query) use ($course) {
                $query->where('course_id', $course->id);
            })->get();

            foreach ($lessons as $lesson) {
                LessonAttendance::create([
                    'attendance_id' => $attendance->id,
                    'lesson_id' => $lesson->id,
                    'status' => LessonAttendance::STATUS_BEFORE_ATTENDANCE,
                ]);
            }
        });
    }
}
