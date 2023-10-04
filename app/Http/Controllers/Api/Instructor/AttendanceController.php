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
        $weekCount = 0;
        $monthCount = 0;
        $yearCount = 0;
        $endDate = now();  
        $weekAgo = $endDate->subWeek(1);
        $monthAgo = $endDate->subMonth(1);
        $yearAgo = $endDate->subYear(1);
        $students = Attendance::where('course_id', $courseId)->get();
        $studentsCount = Attendance::where('course_id', $courseId)->get()->count();

        foreach ($students as $student) {
            $lastLoginDate = Student::findOrFail($student->id)->last_login_at;
            if ($lastLoginDate >= $weekAgo) {
                $weekCount++;
            } 
            if ($lastLoginDate >= $monthAgo) {
                $monthCount++;
            } 
            if ($lastLoginDate >= $yearAgo) {
                $yearCount++;
            }   
        }
        
        if ($period === "7") {
            $last_week_login_rate = $this->loginRateCalculate($weekCount, $studentsCount);
            return response()->json(['last_week_login_rate' => $last_week_login_rate,], 200);
        } elseif ($period === "30") {
            $last_month_login_rate = $this->loginRateCalculate($monthCount, $studentsCount);
            return response()->json(['last_month_login_rate' => $last_month_login_rate,], 200);
        } elseif ($period === "365") {
            $last_year_login_rate = $this->loginRateCalculate($yearCount, $studentsCount);
            return response()->json(['last_year_login_rate' => $last_year_login_rate,], 200);
        } else {
            return response()->json([
                'result' => false, 
                'error_message' => 'Invalid Request Body.',
            ], 400);
        }
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
