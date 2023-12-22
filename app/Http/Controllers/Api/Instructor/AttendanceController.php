<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Model\Chapter;
use App\Model\Attendance;
use Illuminate\Support\Facades\Auth;
use App\Model\Lesson;
use App\Model\Course;
use Illuminate\Support\Carbon;
use App\Model\LessonAttendance;
use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\AttendanceDeleteRequest;
use App\Http\Requests\Instructor\LoginRateRequest;
use App\Http\Requests\Instructor\AttendanceStoreRequest;
use App\Http\Requests\Instructor\AttendanceShowRequest;
use App\Http\Resources\Instructor\AttendanceShowResource;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function store(AttendanceStoreRequest $request)
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
            $lessons = Lesson::whereHas('chapter', function($query) use ($request) {
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
    public function show(AttendanceShowRequest $request) {
        $courseId = $request->course_id;
        $chapters = Chapter::with('lessons.lessonAttendances')->where('course_id', $courseId)->get();
        $studentsCount = Attendance::where('course_id', $courseId)->count();

        foreach ($chapters as $chapter) {
            $completedCount = 0;
            foreach ($chapter->lessons as $lesson) {
                foreach ($lesson->lessonAttendances as $lessonAttendance) {
                    if($lessonAttendance->status === LessonAttendance::STATUS_COMPLETED_ATTENDANCE){
                        $completedCount+=1;
                    }
                }
            }
            $chapter->completedCount = $completedCount;
        }
        return new AttendanceShowResource([
            'chapters' => $chapters,
            'studentsCount' => $studentsCount,
        ]);
    }

    public function delete(AttendanceDeleteRequest $request) 
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function loginRate(LoginRateRequest $request) {
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
            $periodAgo = $endDate->subWeek(1);
        } elseif ($request->period === Attendance::PERIOD_MONTH) {
            $periodAgo = $endDate->subMonth(1);
        } elseif ($request->period === Attendance::PERIOD_YEAR) {
            $periodAgo = $endDate->subYear(1);
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
     * @return int
     */
    public function calcLoginRate($number, $total) {
        if ($total === 0) return 0;

        $percent = ($number / $total) * 100;
        return floor($percent);
    }

}
