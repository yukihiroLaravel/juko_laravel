<?php

namespace App\Services\Attendance;

use App\Enums\Lesson\StatusEnum as LessonStatusEnum;
use App\Model\Attendance;
use App\Model\Course;
use App\Model\Lesson;
use App\Model\LessonAttendance;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StoreService
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

            // 重複登録チェック
            if (Attendance::where('course_id', $course->id)
                ->where('student_id', $studentId)
                ->exists()
            ) {
                throw ValidationException::withMessages([
                    'student_id' => [
                        'Attendance record already exists.',
                    ],
                ]);
            }

            // 講座定員チェック
            if (! $course->hasCapacity()) {
                throw ValidationException::withMessages([
                    'course_id' => [
                        'This course has already reached its capacity.',
                    ],
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

            $lessonIds = Lesson::whereHas('chapter', function ($query) use ($course) {
                $query->where('course_id', $course->id);
            })
                ->whereIn('status', [
                    LessonStatusEnum::PUBLIC->value,
                    LessonStatusEnum::PRIVATE->value,
                ])
                ->pluck('id');

            if ($lessonIds->isNotEmpty()) {
                LessonAttendance::insert(
                    $lessonIds->map(fn ($lessonId) => [
                        'attendance_id' => $attendance->id,
                        'lesson_id' => $lessonId,
                        'status' => LessonAttendance::STATUS_BEFORE_ATTENDANCE,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])->all()
                );
            }
        });
    }
}
