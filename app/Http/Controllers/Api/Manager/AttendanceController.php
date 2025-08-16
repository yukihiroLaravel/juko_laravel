<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\Attendance\DeleteRequest;
use App\Http\Requests\Manager\Attendance\LoginRateRequest;
use App\Http\Requests\Manager\Attendance\ShowRequest;
use App\Http\Requests\Manager\Attendance\ShowStatusRequest;
use App\Http\Requests\Manager\Attendance\StatusRequest;
use App\Http\Requests\Manager\Attendance\StoreRequest;
use App\Http\Resources\Instructor\Attendance\StatusResource;
use App\Http\Resources\Manager\AttendanceShowResource;
use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Lesson;
use App\Model\LessonAttendance;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @tags Manager-Attendance
 */
class AttendanceController extends Controller
{
    /**
     * 受講状況登録API
     */
    public function store(StoreRequest $request): JsonResponse
    {
        /** @var Course $course */
        $course = Course::findOrFail($request->course_id);

        // Policyによる認可チェック
        $this->authorize('create', [Attendance::class, $course]);

        if (Attendance::where('course_id', $request->course_id)
            ->where('student_id', $request->student_id)
            ->exists()
        ) {
            throw new AuthorizationException(
                'Attendance record already exists.'
            );
        }

        DB::beginTransaction();
        try {
            $attendance = Attendance::create([
                'course_id' => $request->course_id,
                'student_id' => $request->student_id,
            ]);

            $lessons = Lesson::whereHas('chapter', function ($query) use ($request) {
                $query->where('course_id', $request->course_id);
            })->get();

            $lessons->each(function (Lesson $lesson) use ($attendance) {
                LessonAttendance::create([
                    'attendance_id' => $attendance->id,
                    'lesson_id' => $lesson->id,
                    'status' => LessonAttendance::STATUS_BEFORE_ATTENDANCE,
                ]);
            });

            DB::commit();

            return response()->json(['result' => true]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            throw $e;
        }
    }

    /**
     * 受講状況取得API
     */
    public function show(ShowRequest $request): AttendanceShowResource
    {
        $courseId = $request->course_id;

        // 現在ログインしているinstructorのidを取得
        $instructorId = Auth::guard('instructor')->user()->id;
        // ログインしている講師とその管理している講師を取得
        $manager = Instructor::with('managings')->find($instructorId);
        // 管理している講師のIDを配列として取得し、自分自身のIDも追加
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $instructorId;

        $course = Course::findOrFail($courseId);
        if (! in_array($course->instructor_id, $instructorIds, true)) {
            // 自分と配下の講師の講座でない場合はエラーを返す
            throw new AuthorizationException(
                'Forbidden, not allowed to access this course.'
            );
        }

        $chapters = Chapter::with([
            'course.tags',
            'lessons.lessonAttendances',
        ])->where('course_id', $courseId)->get();

        /** @var int */
        $studentsCount = Attendance::where('course_id', $courseId)->count();

        return new AttendanceShowResource([
            'chapters' => $chapters,
            'studentsCount' => $studentsCount,
        ]);
    }

    /**
     * 受講状況削除API
     */
    public function delete(DeleteRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $attendanceId = $request->route('attendance_id');
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
            'lessonAttendances.lesson.chapter.lessons',
        ])->where('course_id', $request->course_id)->get();
        $period = $request->period;

        // 完了したレッスンの数を取得
        $completedLessonsCount = $attendances->flatMap(fn (Attendance $attendance) => $attendance->lessonAttendances->filter(function (LessonAttendance $lessonAttendance) use ($period) {
            $updatedAtRequestPeriod = match ($period) {
                LessonAttendance::PERIOD_TODAY => $lessonAttendance->updated_at->isToday(),
                LessonAttendance::PERIOD_MONTH => $lessonAttendance->updated_at->isCurrentMonth(),
                default => throw new Exception('Invalid period'),
            };

            return $lessonAttendance->status === LessonAttendance::STATUS_COMPLETED_ATTENDANCE && $updatedAtRequestPeriod;
        }))->count();

        // 完了したチャプターの数を取得
        $completedChaptersCount = $attendances->flatMap(fn (Attendance $attendance) =>
        // 各出席情報に関連するレッスン出席情報をフィルタリング
        $attendance->lessonAttendances->where('status', LessonAttendance::STATUS_COMPLETED_ATTENDANCE))
            ->filter(function (LessonAttendance $lessonAttendance) use ($period) {
                // チャプターに含まれているすべてのレッスンIDを取得
                $allLessonsId = $lessonAttendance->lesson->chapter->lessons->pluck('id');
                // チャプター内の全レッスン数をカウント
                $totalLessonsCount = $allLessonsId->count();
                // チャプター内で完了したレッスン数をカウント
                $completedLessonsCount = $lessonAttendance->where('attendance_id', $lessonAttendance->attendance_id)
                    ->whereIn('lesson_id', $allLessonsId)
                    ->where('status', LessonAttendance::STATUS_COMPLETED_ATTENDANCE)
                    ->count();

                $updatedAtRequestPeriod = match ($period) {
                    LessonAttendance::PERIOD_TODAY => $lessonAttendance->updated_at->isToday(),
                    LessonAttendance::PERIOD_MONTH => $lessonAttendance->updated_at->isCurrentMonth(),
                    default => throw new Exception('Invalid period'),
                };

                // チャプター内の全レッスンが完了しているかつ、指定期間内に更新されているかをチェック
                return $updatedAtRequestPeriod && ($totalLessonsCount === $completedLessonsCount);
            })
            ->map(fn (LessonAttendance $lessonAttendance) =>
            // chapter_idとattendance_idをキーにもつ新しい配列を作成
            [
                'chapter_id' => $lessonAttendance->lesson->chapter_id,
                'attendance_id' => $lessonAttendance->attendance_id,
            ])
            ->unique() // 重複するチャプターと出席情報の組み合わせを削除
            ->count();

        return response()->json([
            'completed_lessons_count' => $completedLessonsCount,
            'completed_chapters_count' => $completedChaptersCount,
        ]);
    }

    /**
     * 受講状況取得API
     */
    public function status(StatusRequest $request): StatusResource
    {
        $attendanceId = $request->attendance_id;
        $instructorId = Auth::guard('instructor')->user()->id;

        // マネージャーとその配下の講師のIDを取得
        $manager = Instructor::with('managings')->find($instructorId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $instructorId;

        $attendance = Attendance::with([
            'course.chapters.lessons.lessonAttendances',
            'course.tags',
        ])
            ->findOrFail($attendanceId);

        if (! in_array($attendance->course->instructor_id, $instructorIds, true)) {
            throw new AuthorizationException(
                'Forbidden, not allowed to access this course.'
            );
        }

        return new StatusResource($attendance);
    }
}
