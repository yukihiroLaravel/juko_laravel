<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Model\Chapter;
use App\Model\Attendance;
use App\Model\Lesson;
use App\Model\Student;
use App\Model\Course;
use App\Model\LessonAttendance;
use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\AttendanceStoreRequest;
use App\Http\Requests\Instructor\AttendanceShowRequest;
use App\Http\Resources\Instructor\AttendanceShowResource;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

     /**
     * 受講生ログイン率取得API
     * @param string courseId
     * @return json
     */ 
    public function loginRate($courseId, $period) {
        $periodCount = 0;
        $endDate = now();  

        if ($period === "week") {
            $periodAgo = $endDate->subWeek(1);
            $loginRateKey = 'last_week_login_rate';
        } elseif ($period === "month") {
            $periodAgo = $endDate->subMonth(1);
            $loginRateKey = 'last_month_login_rate';
        } elseif ($period === "year") {
            $periodAgo = $endDate->subYear(1);
            $loginRateKey = 'last_year_login_rate';
        } else {
            return response()->json([
                'result' => false, 
                'error_message' => 'Invalid Request Body.',
            ], 400);
        }   

        $students = Attendance::where('course_id', $courseId)->get();
        $studentsCount = $students->count();

        foreach ($students as $student) {
            $lastLoginDate = Student::findOrFail($student->id)->last_login_at;
            if ($lastLoginDate >= $periodAgo) {
                $periodCount++;
            } 
        }

        $loginRate = $this->loginRateCalculate($periodCount, $studentsCount);
        return response()->json([$loginRateKey => $loginRate,], 200);
        
    }

    /**
     * %計算
     * @param int $number, $total, $precision 
     * @return int
     */
    public function loginRateCalculate($number, $total, $precision = 0) {
        if ($number < 0) {
          return 0;
        }
      
        try {
            $percent = ($number / $total) * 100;
            return round($percent, $precision);
        } catch (\Throwable $e) {
            return 0;
        }
    }

}
