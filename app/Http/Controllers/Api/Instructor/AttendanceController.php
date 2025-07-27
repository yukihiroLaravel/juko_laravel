<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\Attendance\DeleteRequest;
use App\Http\Requests\Instructor\Attendance\LoginRateRequest;
use App\Http\Requests\Instructor\Attendance\ShowRequest;
use App\Http\Requests\Instructor\Attendance\ShowStatusRequest;
use App\Http\Requests\Instructor\Attendance\StatusRequest;
use App\Http\Requests\Instructor\Attendance\StoreRequest;
use App\Http\Resources\Instructor\AttendanceShowResource;
use App\Http\Resources\Instructor\AttendanceStatusResource;
use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\Course;
use App\Model\Lesson;
use App\Model\LessonAttendance;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @tags Instructor-Attendance
 */
class AttendanceController extends Controller
{
    public function store(StoreRequest $request): JsonResponse
{
    $attendance = new Attendance([
        'course_id' => $request->course_id,
        'student_id' => $request->student_id,
    ]);

    $attendance->setRelation('course', Course::findOrFail($request->course_id));

    // Policyによる認可チェック
    $this->authorize('create', $attendance);

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

        foreach ($lessons as $lesson) {
            LessonAttendance::create([
                'attendance_id' => $attendance->id,
                'lesson_id' => $lesson->id,
                'status' => LessonAttendance::STATUS_BEFORE_ATTENDANCE,
            ]);
        }

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
        $instructorId = Auth::guard('instructor')->user()->id;
        $courseId = $request->course_id;
        $course = Course::with('tags')->findOrFail($courseId);

        if ($course->instructor_id !== $instructorId) {
            // ログインしている講師の講座でない場合はエラーを返す
            throw new AuthorizationException('Forbidden, invalid instructor_id.');
        }

        /** @var Collection<int, Chapter> */
        $chapters = Chapter::with('lessons.lessonAttendances')->where('course_id', $courseId)->get();

        /** @var int */
        $studentsCount = Attendance::where('course_id', $courseId)->count();

        return new AttendanceShowResource([
            'chapters' => $chapters,
            'studentsCount' => $studentsCount,
            'tags' => $course->tags,
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
        $instructorId = Course::findOrFail($request->course_id)->instructor_id;
        $loginId = Auth::guard('instructor')->user()->id;

        if ($instructorId !== $loginId) {
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
        $instructorId = Auth::guard('instructor')->user()->id;
        $courseId = $request->course_id;
        $course = Course::findOrFail($courseId);

        if ($course->instructor_id !== $instructorId) {
            // ログインしている講師の講座でない場合はエラーを返す
            throw new AuthorizationException('Forbidden, invalid instructor_id.');
        }

        $attendances = Attendance::with([
            'lessonAttendances.lesson.chapter.course',
            'lessonAttendances.lesson.chapter.lessons',
        ])->where('course_id', $courseId)->get();
        $period = $request->period;

        // 指定期間内に完了したレッスンの個数を取得
        $completedLessonsCount = $attendances->flatMap(fn (Attendance $attendance) => $attendance->lessonAttendances->filter(function (LessonAttendance $lessonAttendance) use ($period) {
            if ($period === LessonAttendance::PERIOD_TODAY) {
                $updatedAtRequestPeriod = $lessonAttendance->updated_at->isToday();
            } elseif ($period === LessonAttendance::PERIOD_MONTH) {
                $updatedAtRequestPeriod = $lessonAttendance->updated_at->isCurrentMonth();
            } else {
                throw new Exception('Invalid period');
            }

            return $lessonAttendance->status === LessonAttendance::STATUS_COMPLETED_ATTENDANCE && $updatedAtRequestPeriod;
        }))->count();

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
        ]);
    }

    /**
     * 受講状況API
     */
    public function status(StatusRequest $request): AttendanceStatusResource
    {
        $attendanceId = $request->attendance_id;

        $attendance = Attendance::with(['course.chapters.lessons.lessonAttendances'])->findOrFail($attendanceId);
        assert($attendance instanceof Attendance);

        if (Auth::guard('instructor')->user()->id !== $attendance->course->instructor_id) {
            throw new AuthorizationException(
                'Forbidden, not allowed to access this course.'
            );
        }

        return new AttendanceStatusResource($attendance);
    }
}
