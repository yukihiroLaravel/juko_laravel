<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Model\Attendance;
use App\Model\Lesson;
use App\Model\LessonAttendance;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function store(Request $request)
    {
        $validateData = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'student_id' => 'required|exists:students,id',
        ]);

        $existingAttendance = Attendance::where('course_id', $validateData['course_id'])
            ->where('student_id', $validateData['student_id'])
            ->first();

        if ($existingAttendance) {
            return response()->json([
                'error' => '既に登録されています'], 409);
        }

        $attendance = Attendance::create([
            'course_id'  => $validateData['course_id'],
            'student_id' => $validateData['student_id'],
            'progress'   => 0
        ]);

        $lessons = Lesson::whereHas('chapter', function($query) use ($validateData) {
            $query->where('course_id', $validateData['course_id']);
        })->get();

        foreach ($lessons as $lesson) {
            LessonAttendance::create([
                'attendance_id' => $attendance->id,
                'lesson_id'     => $lesson->id,
                'status'        => 'before_attendance'
            ]);
        }

        return response()->json(['message' => '登録しました'], 201);
    }
}
