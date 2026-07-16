<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Enums\Lesson\StatusEnum as LessonStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\Attendance\DeleteRequest;
use App\Http\Requests\Instructor\Attendance\ExpiringRequest;
use App\Http\Requests\Instructor\Attendance\FollowUpRequest;
use App\Http\Requests\Instructor\Attendance\LoginRateRequest;
use App\Http\Requests\Instructor\Attendance\ShowRequest;
use App\Http\Requests\Instructor\Attendance\ShowStatusRequest;
use App\Http\Requests\Instructor\Attendance\StatusRequest;
use App\Http\Requests\Instructor\Attendance\StoreRequest;
use App\Http\Requests\Instructor\Attendance\StuckPointsRequest;
use App\Http\Resources\Common\Attendance\StuckPointsResource;
use App\Http\Resources\Instructor\Attendance\ExpiringResource;
use App\Http\Resources\Instructor\Attendance\FollowUpResource;
use App\Http\Resources\Instructor\Attendance\StatusResource;
use App\Http\Resources\Instructor\AttendanceShowResource;
use App\Model\Attendance;
use App\Model\Course;
use App\Model\LessonAttendance;
use App\Services\Attendance\CalculateDeadlineService;
use App\Services\Attendance\ExpiringService;
use App\Services\Attendance\FollowUpService;
use App\Services\Attendance\ShowService;
use App\Services\Attendance\StoreService;
use App\Services\Attendance\StuckPointsService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @tags Instructor-Attendance
 */
class AttendanceController extends Controller
{
    /**
     * 受講状況登録API
     */
    public function store(StoreRequest $request, CalculateDeadlineService $calculateDeadline, StoreService $service): JsonResponse
    {
        $course = Course::findOrFail($request->course_id);

        // Policyによる認可チェック
        $this->authorize('create', [Attendance::class, $course]);

        $service(
            $request->course_id,
            $request->student_id,
            $calculateDeadline
        );

        return response()->json(['result' => true]);
    }

    /**
     * 受講状況取得API
     */
    public function show(ShowRequest $request, ShowService $service): AttendanceShowResource
    {
        $attendance = Attendance::with(['course.tags', 'course.courseDeadline'])->findOrFail($request->attendance_id);

        $this->authorize('view', [Attendance::class, $attendance]);

        $result = $service($attendance->course);

        return new AttendanceShowResource([
            'attendance' => $attendance,
            'studentsCount' => $result['studentsCount'],
            'chapters' => $result['chapters'],
        ]);
    }

    /**
     * 受講状況削除API
     */
    public function delete(DeleteRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $attendanceId = $request->attendance_id;
            $attendance = Attendance::with('course.instructor')->findOrFail($attendanceId);

            $this->authorize('delete', $attendance);

            // 受講状況に紐づくレッスン受講状況を削除
            $attendance->delete();

            DB::commit();

            return response()->json([
                'result' => true,
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            throw $e;
        }
    }

    /**
     * 受講生ログイン率取得API
     */
    public function loginRate(LoginRateRequest $request): JsonResponse
    {
        $courseId = $request->course_id;
        $course = Course::findOrFail($courseId);
        // 担当講師またはマネージャー権限のある講師以外は403エラーを返す
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
            if ($lastLoginDate->gte($periodAgo)) {
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
        $courseId = $request->course_id;
        $course = Course::findOrFail($courseId);

        // ログインしている講師の講座でない場合は403エラーを返す
        $this->authorize('view', $course);

        $attendances = Attendance::with([
            'lessonAttendances.lesson.chapter.course',
            'lessonAttendances.lesson.chapter.lessons',
        ])->where('course_id', $courseId)->get();

        $studentsCount = $attendances->count();

        $totalLessonsCount = $course->chapters()
            ->withCount(['lessons' => fn ($query) => $query->public()])
            ->get()
            ->sum('lessons_count');

        $period = $request->period;

        // 指定期間内に完了した公開レッスンの個数を取得
        $completedLessonsCount = $attendances->flatMap(fn (Attendance $attendance) => $attendance->lessonAttendances->filter(function (LessonAttendance $lessonAttendance) use ($period) {
            if ($lessonAttendance->lesson->status !== LessonStatusEnum::PUBLIC) {
                return false;
            }
            if ($period === LessonAttendance::PERIOD_TODAY) {
                $updatedAtRequestPeriod = $lessonAttendance->updated_at->isToday();
            } elseif ($period === LessonAttendance::PERIOD_MONTH) {
                $updatedAtRequestPeriod = $lessonAttendance->updated_at->isCurrentMonth();
            } else {
                throw new Exception('Invalid period');
            }

            return $lessonAttendance->status === LessonAttendance::STATUS_COMPLETED_ATTENDANCE && $updatedAtRequestPeriod;
        }))->count();

        // 指定期間内に完了したレッスン数をもとに平均進捗率を取得
        $averageProgressRate = Attendance::calcAverageProgressRate(
            $completedLessonsCount,
            $studentsCount,
            $totalLessonsCount,
        );

        // 全公開レッスンを完了している受講生数を取得
        $completedStudentsCount = $attendances
            ->filter(fn (Attendance $attendance) => $attendance->isAllPublicLessonsCompleted($totalLessonsCount)
            )
            ->count();

        // 修了率を取得
        $completionRate = Attendance::calcCompletionRate(
            $completedStudentsCount,
            $studentsCount,
        );

        // 指定期間内に完了したチャプターの個数を取得
        $completedChaptersCount = $attendances->flatMap(fn (Attendance $attendance) => $attendance->lessonAttendances->where('status', LessonAttendance::STATUS_COMPLETED_ATTENDANCE))
            ->filter(function (LessonAttendance $lessonAttendance) use ($period) {
                // チャプターに含まれているレッスンが全て完了されているかつ、最新のレッスンの完了済みステータスの更新日時が指定期間のもので絞り込む
                $allLessonsId = $lessonAttendance->lesson->chapter->lessons->pluck('id');
                $totalLessonsCount = $allLessonsId->count();
                $completedLessonsCount = $lessonAttendance->where('attendance_id', $lessonAttendance->attendance_id)
                    ->whereIn('lesson_id', $allLessonsId)
                    ->where('status', LessonAttendance::STATUS_COMPLETED_ATTENDANCE)
                    ->count();
                if ($period === LessonAttendance::PERIOD_TODAY) {
                    $updatedAtRequestPeriod = $lessonAttendance->updated_at->isToday();
                } elseif ($period === LessonAttendance::PERIOD_MONTH) {
                    $updatedAtRequestPeriod = $lessonAttendance->updated_at->isCurrentMonth();
                } else {
                    throw new Exception('Invalid period');
                }

                return $updatedAtRequestPeriod && $totalLessonsCount === $completedLessonsCount;
            })
            ->map(fn (LessonAttendance $lessonAttendance) =>
            // chapter_idとattendance_idをキーにもつ新しい配列を作成
            [
                'chapter_id' => $lessonAttendance->lesson->chapter_id,
                'attendance_id' => $lessonAttendance->attendance_id,
            ])
            ->unique()
            ->count();

        return response()->json([
            'completed_lessons_count' => $completedLessonsCount,
            'completed_chapters_count' => $completedChaptersCount,
            'average_progress_rate' => $averageProgressRate,
            'completion_rate' => $completionRate,
        ]);
    }

    /**
     * 受講状況API
     */
    public function status(StatusRequest $request): StatusResource
    {
        $attendanceId = $request->attendance_id;

        /** @var Attendance $attendance */
        $attendance = Attendance::with(['course.chapters.lessons.lessonAttendances'])->findOrFail($attendanceId);

        // Policyによる認可チェック
        $this->authorize('view', $attendance->course);

        return new StatusResource($attendance);
    }

    /**
     * 要フォロー受講生API
     */
    public function followUp(FollowUpRequest $request, FollowUpService $service): AnonymousResourceCollection
    {
        $course = Course::findOrFail($request->course_id);
        $this->authorize('view', $course);

        $students = $service($course, $request->days);

        return FollowUpResource::collection($students);
    }

    /**
     * 近日期限切れ予定受講生API
     */
    public function expiring(ExpiringRequest $request, ExpiringService $service): AnonymousResourceCollection
    {
        $course = Course::findOrFail($request->course_id);
        $this->authorize('view', $course);

        $result = $service($request->course_id, $request->thresholds);

        return ExpiringResource::collection($result);
    }

    /**
     * 受講生の止まっている箇所取得API
     */
    public function stuckPoints(StuckPointsRequest $request, StuckPointsService $service): AnonymousResourceCollection
    {
        $courseId = $request->course_id;

        // Policyによる認可チェック
        $course = Course::findOrFail($courseId);
        $this->authorize('view', $course);

        $result = $service($courseId);

        return StuckPointsResource::collection($result);
    }
}
