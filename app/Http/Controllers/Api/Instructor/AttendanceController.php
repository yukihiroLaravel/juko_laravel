<?php

namespace App\Http\Controllers\Api\Instructor;

use Exception;
use App\Model\Course;
use App\Model\Lesson;
use App\Model\Chapter;
use App\Model\Attendance;
use Illuminate\Support\Carbon;
use App\Model\LessonAttendance;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;
use App\Http\Requests\Instructor\LoginRateRequest;
use App\Http\Requests\Instructor\AttendanceShowRequest;
use App\Http\Requests\Instructor\AttendanceStoreRequest;
use App\Http\Requests\Instructor\AttendanceDeleteRequest;
use App\Http\Resources\Instructor\AttendanceShowResource;

class AttendanceController extends Controller
{
    /**
     * 受講状況登録API
     *
     * @param AttendanceStoreRequest $request
     * @return JsonResponse
     */
    public function store(AttendanceStoreRequest $request): JsonResponse
    {
        $attendance = Attendance::where('course_id', $request->course_id)
            ->where('student_id', $request->student_id)
            ->first();

        if ($attendance) {
            return response()->json([
                'result' => false,
                'message' => 'Attendance record already exists.'
            ], 409);
        }

        DB::beginTransaction();
        try {
            $attendance = Attendance::create([
                'course_id'  => $request->course_id,
                'student_id' => $request->student_id,
                'progress'   => Attendance::PROGRESS_DEFAULT_VALUE
            ]);
            $lessons = Lesson::whereHas('chapter', function ($query) use ($request) {
                $query->where('course_id', $request->course_id);
            })->get();
            foreach ($lessons as $lesson) {
                LessonAttendance::create([
                    'attendance_id' => $attendance->id,
                    'lesson_id'     => $lesson->id,
                    'status'        => LessonAttendance::STATUS_BEFORE_ATTENDANCE
                ]);
            }
            DB::commit();
            return response()->json([
                'result' => true,
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return response()->json([
                'result' => false,
            ], 500);
        }
    }

    /**
     * 受講状況取得API
     *
     * @param AttendanceShowRequest $request
     * @return AttendanceShowResource
     */
    public function show(AttendanceShowRequest $request): AttendanceShowResource
    {
        $courseId = $request->course_id;

        /** @var Collection<Chapter> */
        $chapters = Chapter::with('lessons.lessonAttendances')->where('course_id', $courseId)->get();

        /** @var int */
        $studentsCount = Attendance::where('course_id', $courseId)->count();

        $chapters->each(function (Chapter $chapter) {
            $completedCount = $chapter->lessons->flatMap(function (Lesson $lesson) {
                return $lesson->lessonAttendances->where('status', LessonAttendance::STATUS_COMPLETED_ATTENDANCE);
            })->count();
            $chapter->completed_count = $completedCount;
        });

        return new AttendanceShowResource([
            'chapters' => $chapters,
            'studentsCount' => $studentsCount,
        ]);
    }

    /**
     * 受講状況削除API
     *
     * @param AttendanceDeleteRequest $request
     * @return JsonResponse
     */
    public function delete(AttendanceDeleteRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $attendanceId = $request->route('attendance_id');
            $attendance = Attendance::with('lessonAttendances')->findOrFail($attendanceId);

            if (Auth::guard('instructor')->user()->id !== $attendance->course->instructor_id) {
                return response()->json([
                    "result" => false,
                    "message" => "Unauthorized: The authenticated instructor does not have permission to delete this attendance record",
                ], 403);
            }

            $attendance->delete();

            DB::commit();

            return response()->json([
                "result" => true,
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return response()->json([
                "result" => false,
            ], 500);
        }
    }

    /**
     * 受講生ログイン率取得API
     *
     * @param LoginRateRequest $request
     * @return JsonResponse
     */
    public function loginRate(LoginRateRequest $request): JsonResponse
    {
        $instructorId = Course::findOrFail($request->course_id)->instructor_id;
        $loginId = Auth::guard('instructor')->user()->id;

        if ($instructorId !== $loginId) {
            return response()->json([
                'result' => 'false',
                'message' => 'You could not get login rate'
            ], 403);
        }

        $endDate = new Carbon();

        if ($request->period === Attendance::PERIOD_WEEK) {
            $periodAgo = $endDate->subWeek();
        } elseif ($request->period === Attendance::PERIOD_MONTH) {
            $periodAgo = $endDate->subMonth();
        } elseif ($request->period === Attendance::PERIOD_YEAR) {
            $periodAgo = $endDate->subYear();
        } else {
            return response()->json([
                'result' => 'false',
                'message' => 'You could not get login rate'
            ], 400);
        }

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

        $loginRate = $this->calcLoginRate($loginCount, $studentsCount);
        return response()->json(['login_rate' => $loginRate], 200);
    }

    /**
     * 受講生ログイン率計算
     *
     * @param int $number
     * @param int $total
     * @return float
     */
    public function calcLoginRate(int $number, int $total): float
    {
        if ($total === 0) {
            return 0;
        }

        $percent = ($number / $total) * 100;
        return floor($percent);
    }

    /**
     * 講座受講状況-当日
     *
     * @param AttendanceShowRequest $request
     */
    public function showStatusToday(AttendanceShowRequest $request): JsonResponse
    {
        $completedLessonsCount = LessonAttendance::with(['attendance' => function ($query) use ($request) {
            $query->where('course_id', $request->course_id);
        }])->where('status', 'completed_attendance')->whereDate('updated_at', Carbon::today())->count();

        // withCountとcount()どちらを使う方がいいでしょうか？
        function chapterTotalLessonCount($chapter_id)
        {
            $lesson = Lesson::where('chapter_id', $chapter_id);
            return $lesson->count();
        }

        function chaptercompleatedLessonCount($chapter_id, $attendanceId)
        {
            $lessons = Lesson::with(['lessonAttendances' => function ($query) use ($attendanceId) {
                $query->where('attendance_id', $attendanceId)->where('status', 'completed_attendance');
            }])->where('chapter_id', $chapter_id)->get()->toArray();
            $lessons = array_column($lessons, 'lesson_attendances');
            $lessons = array_filter($lessons);

            return count($lessons);
        }
        $test = chaptercompleatedLessonCount(2, 1);

        $completedChaptersCount = 0;

        $attendances = Attendance::where('course_id', $request->course_id)->get();


        $attendances->each(function ($attendance) use ($completedChaptersCount) {
            // 今日完了したという条件をどのようにつけるのかわからず、困っています。
            $chaptersId = Chapter::whereHas('course.attendances', function ($query) use ($attendance) {
                $query->where('id', $attendance->id);
            })->pluck('id');

            $chaptersId->each(function ($chapterId) use ($completedChaptersCount, $attendance) {
                $chaptersTotalLessonCount = chapterTotalLessonCount($chapterId);
                $chapterCompleatedLessonCount = chaptercompleatedLessonCount($chapterId, $attendance->id);

                if ($chaptersTotalLessonCount === $chapterCompleatedLessonCount) {
                    $completedChaptersCount += 1;
                }
            });
            // ifの後にdd($completedChaptersCount);をすると1が帰ってくるのですが、ここですると0になる理由がわからず、困っています。
            dd($completedChaptersCount);
        });
        return response()->json([
            'completed_lessons_count' => $completedLessonsCount,
            'completed_chapters_count' =>  $completedChaptersCount
        ]);
    }
}
