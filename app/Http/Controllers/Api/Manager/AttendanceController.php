<?php

namespace App\Http\Controllers\Api\Manager;

use App\Enums\Chapter\StatusEnum as ChapterStatusEnum;
use App\Enums\Lesson\StatusEnum as LessonStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\Attendance\LoginRateRequest;
use App\Http\Requests\Manager\Attendance\ShowStatusRequest;
use App\Model\Attendance;
use App\Model\Course;
use App\Model\LessonAttendance;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @tags Manager-Attendance
 */
class AttendanceController extends Controller
{
    /**
     * 受講生ログイン率取得API
     */
    public function loginRate(LoginRateRequest $request): JsonResponse
    {
        $course = Course::findOrFail($request->course_id);

        // Policyによる認可処理
        $this->authorize('view', $course);

        $nowDate = new Carbon;

        $periodAgo = match ($request->period) {
            Attendance::PERIOD_WEEK => $nowDate->copy()->subWeek(),
            Attendance::PERIOD_MONTH => $nowDate->copy()->subMonth(),
            Attendance::PERIOD_YEAR => $nowDate->copy()->subYear(),
            default => throw new Exception('Invalid period. ['.$request->period.']'),
        };

        $attendances = Attendance::with('student')->where('course_id', $request->course_id)->get();
        $studentsCount = $attendances->count();

        // 期間内にログインした受講生数
        $loginCount = 0;

        foreach ($attendances as $attendance) {
            $lastLoginDate = $attendance->student->last_login_at;
            if ($lastLoginDate !== null && $lastLoginDate->gte($periodAgo)) {
                $loginCount++;
            }
        }

        $loginRate = Attendance::calcLoginRate($loginCount, $studentsCount);

        return response()->json(['login_rate' => $loginRate], 200);
    }

    /**
     * 完了済みレッスン数と完了済みチャプター数取得API
     */
    public function showStatus(ShowStatusRequest $request): JsonResponse
    {
        $course = Course::findOrFail($request->course_id);

        // Policyによる認可処理
        $this->authorize('view', $course);

        // 出席情報（関連する情報を含む）を取得
        $attendances = Attendance::with([
            'lessonAttendances.lesson.chapter.course',
            'lessonAttendances.lesson.chapter.publicLessons',
        ])->where('course_id', $request->course_id)->get();
        $period = $request->period;

        // 指定期間内に完了した公開レッスンの個数を取得
        $completedLessonsCount = $attendances->flatMap(fn (Attendance $attendance) => $attendance->lessonAttendances->filter(function (LessonAttendance $lessonAttendance) use ($period) {
            if ($lessonAttendance->lesson->status !== LessonStatusEnum::PUBLIC
                || $lessonAttendance->lesson->chapter->status !== ChapterStatusEnum::PUBLIC
                || $lessonAttendance->completed_at === null) {
                return false;
            }

            return match ($period) {
                LessonAttendance::PERIOD_TODAY => $lessonAttendance->completed_at->isToday(),
                LessonAttendance::PERIOD_MONTH => $lessonAttendance->completed_at->isCurrentMonth(),
                default => throw new Exception('Invalid period'),
            };
        }))->count();

        // 指定期間内に完了したチャプターの個数を取得
        $completedChaptersCount = $attendances->sum(fn (Attendance $attendance) => $attendance->lessonAttendances
            ->filter(fn (LessonAttendance $lessonAttendance) => $lessonAttendance->lesson->status === LessonStatusEnum::PUBLIC
                && $lessonAttendance->lesson->chapter->status === ChapterStatusEnum::PUBLIC)
            ->groupBy('lesson.chapter_id')
            ->filter(function (Collection $lessonAttendancesInChapter) use ($period) {
                $completedAtByLessonId = $lessonAttendancesInChapter
                    ->whereNotNull('completed_at')
                    ->mapWithKeys(fn (LessonAttendance $lessonAttendance) => [$lessonAttendance->lesson_id => $lessonAttendance->completed_at]);

                $publicLessonIdsInChapter = $lessonAttendancesInChapter
                    ->first()
                    ->lesson
                    ->chapter
                    ->publicLessons
                    ->pluck('id');

                if ($publicLessonIdsInChapter->diff($completedAtByLessonId->keys())->isNotEmpty()) {
                    return false;
                }

                $chapterCompletedAt = $completedAtByLessonId->max();

                return match ($period) {
                    LessonAttendance::PERIOD_TODAY => $chapterCompletedAt->isToday(),
                    LessonAttendance::PERIOD_MONTH => $chapterCompletedAt->isCurrentMonth(),
                    default => throw new Exception('Invalid period'),
                };
            })
            ->count());

        return response()->json([
            'completed_lessons_count' => $completedLessonsCount,
            'completed_chapters_count' => $completedChaptersCount,
        ]);
    }
}
