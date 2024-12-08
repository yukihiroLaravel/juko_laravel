<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\AttendanceDeleteRequest;
use App\Http\Requests\Instructor\AttendanceShowRequest;
use App\Http\Requests\Instructor\AttendanceShowStatusRequest;
use App\Http\Requests\Instructor\AttendanceStatusRequest;
use App\Http\Requests\Instructor\AttendanceStoreRequest;
use App\Http\Requests\Instructor\LoginRateRequest;
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

class AttendanceController extends Controller
{
    /**
     * 受講状況登録API
     */
    public function store(AttendanceStoreRequest $request): JsonResponse
    {
        $attendance = Attendance::where('course_id', $request->course_id)
            ->where('student_id', $request->student_id)
            ->first();

        if ($attendance) {
            throw new AuthorizationException(
                'Attendance record already exists.'
            );
        }

        DB::beginTransaction();
        try {
            $attendance = Attendance::create([
                'course_id' => $request->course_id,
                'student_id' => $request->student_id,
                'progress' => Attendance::PROGRESS_DEFAULT_VALUE,
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

            return response()->json([
                'result' => true,
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            throw $e;
        }
    }

    /**
     * 受講状況取得API
     */
    public function show(AttendanceShowRequest $request): AttendanceShowResource
    {
        $courseId = $request->course_id;

        /** @var Collection<int, Chapter> */
        $chapters = Chapter::with('lessons.lessonAttendances')->where('course_id', $courseId)->get();

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
    public function delete(AttendanceDeleteRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $attendanceId = $request->route('attendance_id');
            $attendance = Attendance::with('lessonAttendances')->findOrFail($attendanceId);

            if (Auth::guard('instructor')->user()->id !== $attendance->course->instructor_id) {
                throw new AuthorizationException(
                    'Unauthorized: The authenticated instructor does not have permission to delete this attendance record.'
                );
            }

            $attendance->delete();

            DB::commit();

            return response()->json([
                'result' => true,
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
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
    public function showStatus(AttendanceShowStatusRequest $request): JsonResponse
    {
        $attendances = Attendance::with('lessonAttendances.lesson.chapter.course')->where('course_id', $request->course_id)->get();
        $period = $request->period;

        // 指定期間内に完了したレッスンの個数を取得
        $completedLessonsCount = $attendances->flatMap(function (Attendance $attendance) use ($period) {
            $completedLessonAttendances = $attendance->lessonAttendances->filter(function (LessonAttendance $lessonAttendance) use ($period) {
                if ($period === LessonAttendance::PERIOD_TODAY) {
                    $updatedAtRequestPeriod = $lessonAttendance->updated_at->isToday();
                } elseif ($period === LessonAttendance::PERIOD_MONTH) {
                    $updatedAtRequestPeriod = $lessonAttendance->updated_at->isCurrentMonth();
                } else {
                    throw new Exception('Invalid period');
                }

                return $lessonAttendance->status === LessonAttendance::STATUS_COMPLETED_ATTENDANCE && $updatedAtRequestPeriod;
            });

            return $completedLessonAttendances;
        })->count();

        // 指定期間内に完了したチャプターの個数を取得
        $completedChaptersCount = $attendances->flatMap(function (Attendance $attendance) {
            return $attendance->lessonAttendances->where('status', LessonAttendance::STATUS_COMPLETED_ATTENDANCE);
        })
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
            ->map(function (LessonAttendance $lessonAttendance) {
                // chapter_idとattendance_idをキーにもつ新しい配列を作成
                return [
                    'chapter_id' => $lessonAttendance->lesson->chapter_id,
                    'attendance_id' => $lessonAttendance->attendance_id,
                ];
            })
            ->unique()
            ->count();

        return response()->json([
            'completed_lessons_count' => $completedLessonsCount,
            'completed_chapters_count' => $completedChaptersCount,
        ]);
    }

    /**
     * 受講状況API
     *
     * @return AttendanceStatusResource|JsonResponse
     */
    public function status(AttendanceStatusRequest $request)
    {
        $attendanceId = $request->attendance_id;

        /** @var Attendance */
        $attendance = Attendance::with(['course.chapters.lessons.lessonAttendances'])->findOrFail($attendanceId);

        if (Auth::guard('instructor')->user()->id !== $attendance->course->instructor_id) {
            throw new AuthorizationException(
                'Forbidden, not allowed to access this course.'
            );
        }

        return new AttendanceStatusResource($attendance);
    }
}
