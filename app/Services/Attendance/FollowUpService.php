<?php

namespace App\Services\Attendance;

use App\Model\Attendance;
use App\Model\Course;
use App\Model\LessonAttendance;
use App\Model\Student;
use App\Model\StudentLoginHistory;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class FollowUpService
{
    /**
     * 要フォロー受講生を取得する
     *
     * @param  int  $course_id  講座ID
     * @param  int  $days  最終ログインからの日数
     */
    public function __invoke(int $course_id, int $days): Collection
    {
        $threshold = CarbonImmutable::now()->subDays($days);

        $students = Student::whereHas('attendances', fn ($q) => $q->where('course_id', $course_id))
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

        $course = Course::with([
            'chapters' => fn ($q) => $q->orderBy('order'),
            'chapters.lessons',
        ])->findOrFail($course_id);

        $attendances = Attendance::with('lessonAttendances')
            ->where('course_id', $course_id)
            ->whereIn('student_id', $students->pluck('id'))
            ->get();

        foreach ($students as $student) {
            $attendance = $attendances->firstWhere('student_id', $student->id);

            if (! $attendance) {
                $student->setAttribute('incomplete_chapter_name', null);
                continue;
            }

            $completedLessonIds = $attendance->lessonAttendances
                ->where('status', LessonAttendance::STATUS_COMPLETED_ATTENDANCE)
                ->pluck('lesson_id');

            $incompleteChapter = $course->chapters->first(function ($chapter) use ($completedLessonIds) {
                $lessonIds = $chapter->lessons->pluck('id');

                if ($lessonIds->isEmpty()) {
                    return false;
                }

                return $lessonIds->diff($completedLessonIds)->isNotEmpty();
            });

            $student->setAttribute('incomplete_chapter_name', $incompleteChapter?->title);
        }

        return $students;
    }
}
