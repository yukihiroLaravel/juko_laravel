<?php

namespace App\Http\Controllers\Api\Students;

use App\Http\Controllers\Controller;
use App\Model\Student;
use App\Model\Attendance;
//use App\Model\LessonAttendance;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StudentController extends Controller
{

  /**
   * 
   *
   * @param Request $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function edit($student_id)
  {
    $students = Student::findOrFail($student_id);

    $attendances = Attendance::where('student_id', $student_id)->get();

    //$lessonAttendances = LessonAttendance::where('student_id', $student_id)->get();

    return response()->json([]);
  }
}
