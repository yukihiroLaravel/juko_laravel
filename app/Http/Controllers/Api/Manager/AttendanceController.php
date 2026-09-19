<?php

namespace App\Http\Controllers\Api\Manager;

use App\Enums\Chapter\StatusEnum as ChapterStatusEnum;
use App\Enums\Lesson\StatusEnum as LessonStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\Attendance\LoginRateRequest;
use App\Http\Requests\Manager\Attendance\ShowStatusRequest;
use App\Model\Attendance;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\LessonAttendance;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

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
        // 現在ログインしているinstructorのidを取得
        $instructorId = Auth::guard('instructor')->user()->id;
        // ログインしている講師とその管理している講師を取得
        $manager = Instructor::with('managings')->find($instructorId);
        // 管理している講師のIDを配列として取得し、自分自身のIDも追加
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $instructorId;

        $course = Course::findOrFail($request->course_id);
        if (! in_array($course->instructor_id, $instructorIds, true)) {
            // 自分と配下の講師の講座でない場合はエラーを返す
            throw new AuthorizationException(
                'Forbidden, not allowed to access this course.'
            );
        }

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
        // 現在ログインしているinstructorのidを取得
        $instructorId = Auth::guard('instructor')->user()->id;
        // 現在の講師（マネージャー）とその管理下の講師情報を取得
        $manager = Instructor::with('managings')->find($instructorId);
        // 管理している講師のIDを取得して配列に変換し、マネージャー本人のIDを追加
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $instructorId;

        $course = Course::findOrFail($request->course_id);
        if (! in_array($course->instructor_id, $instructorIds, true)) {
            // 自分と配下の講師の講座でない場合はエラーを返す
            throw new AuthorizationException(
                'Forbidden, not allowed to access this course.'
            );
        }

        // 出席情報（関連する情報を含む）を取得
        $attendances = Attendance::with([
            'lessonAttendances.lesson.chapter.course',
            'lessonAttendances.lesson.chapter.publicLessons',
        ])->where('course_id', $request->course_id)->get();
        $period = $request->period;

        // 指定期間内に完了した公開レッスンの個数を取得
        $completedLessonsCount = $attendances->flatMap(fn (Attendance $attendance) => $attendance->lessonAttendances->filter(function (LessonAttendance $lessonAttendance) use ($period) {
            if ($lessonAttendance->lesson->status !== LessonStatusEnum::PUBLIC || $lessonAttendance->completed_at === null) {
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
