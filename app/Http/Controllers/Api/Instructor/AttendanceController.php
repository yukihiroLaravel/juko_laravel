<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Model\Attendance;
use App\Model\Lesson;
use App\Model\LessonAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function store(Request $request)
    {
        $validateData = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'student_id' => 'required|exists:students,id',
        ]);

        $foundAttendance = Attendance::where('course_id', $validateData['course_id'])
            ->where('student_id', $validateData['student_id'])
            ->first();

        if ($foundAttendance) {
            return response()->json([
                'result' => false,
                'message' => 'Not Found Lesson.'
            ], 404);
        }

        DB::transaction(function () use ($validateData) {
            $attendance = Attendance::create([
                'course_id'  => $validateData['course_id'],
                'student_id' => $validateData['student_id'],
                'progress'   => Attendance::PROGRESS_DEFAULT_VALUE
            ]);

            $lessons = Lesson::whereHas('chapter', function($query) use ($validateData) {
                $query->where('course_id', $validateData['course_id']);
            })->get();

            foreach ($lessons as $lesson) {
                LessonAttendance::create([
                    'attendance_id' => $attendance->id,
                    'lesson_id'     => $lesson->id,
                    'status'        => LessonAttendance::STATUS_BEFORE_ATTENDANCE
                ]);
            }
        });

        return response()->json([
            'result' => true,
        ]);
    }
}
