<?php

namespace App\Services\Attendance;

use App\Dto\Instructor\Attendance\FollowUpStudentDto;
use App\Model\Attendance;
use App\Model\Course;
use App\Model\Student;
use App\Model\StudentLoginHistory;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class FollowUpService
{
    /**
     * 要フォロー受講生を取得する
     *
     * @param  Course  $course  講座
     * @param  int  $days  最終ログインからの日数
     */
    public function __invoke(Course $course, int $days): Collection
    {
        $threshold = CarbonImmutable::now()->subDays($days);

        $students = Student::whereHas('attendances', fn ($q) => $q->where('course_id', $course->id))
            ->select([
                'students.id',
                'students.last_name',
                'students.first_name',
                'students.email',
            ])
            ->addSelect([
                'latest_login_at' => StudentLoginHistory::select('logged_in_at')
                    ->whereColumn('student_id', 'students.id')
                    ->latest('logged_in_at')
                    ->limit(1),
            ])
            ->whereDoesntHave('loginHistories', fn ($q) => $q->where('logged_in_at', '>', $threshold))
            ->orderByRaw('latest_login_at IS NULL DESC, latest_login_at ASC')
            ->get();

        $course->load([
            'chapters' => fn ($q) => $q->orderBy('order'),
            'chapters.lessons',
        ]);

        return $this->buildFollowUpStudentDtos($students, $course);
    }

    private function buildFollowUpStudentDtos(Collection $students, Course $course): Collection
    {
        $attendances = Attendance::with([
            'lessonAttendances' => fn ($q) => $q->whereNotNull('completed_at'),
        ])
            ->where('course_id', $course->id)
            ->whereIn('student_id', $students->pluck('id'))
            ->get();

        return $students->map(function (Student $student) use ($attendances, $course) {
            /** @var Attendance|null $attendance */
            $attendance = $attendances->firstWhere('student_id', $student->id);

            if (! $attendance) {
                return new FollowUpStudentDto(
                    studentId: $student->id,
                    lastName: $student->last_name,
                    firstName: $student->first_name,
                    email: $student->email,
                    latestLoginAt: $student->latest_login_at
                        ? CarbonImmutable::parse($student->latest_login_at)->toDateTimeString()
                        : null,
                    incompleteChapterId: null,
                    incompleteChapterTitle: null,
                );
            }

            $completedLessonIds = $attendance->lessonAttendances
                ->pluck('lesson_id');

            $incompleteChapter = $course->chapters->first(function ($chapter) use ($completedLessonIds) {
                $lessonIds = $chapter->lessons->pluck('id');

                if ($lessonIds->isEmpty()) {
                    return false;
                }

                return $lessonIds->diff($completedLessonIds)->isNotEmpty();
            });

            return new FollowUpStudentDto(
                studentId: $student->id,
                lastName: $student->last_name,
                firstName: $student->first_name,
                email: $student->email,
                latestLoginAt: $student->latest_login_at
                    ? CarbonImmutable::parse($student->latest_login_at)->toDateTimeString()
                    : null,
                incompleteChapterId: $incompleteChapter?->id,
                incompleteChapterTitle: $incompleteChapter?->title,
            );
        });
    }
}
