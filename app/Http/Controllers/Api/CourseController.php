<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CoursesGetRequest;
use App\Http\Resources\CoursesGetResponse;
use App\Model\Attendance;

class CourseController extends Controller
{
    public function index(CoursesGetRequest $request)
    {
        $attendances = Attendance::with(['course.instructor'])->where('student_id', $request->student_id)->get();
        return new CoursesGetResponse($attendances);
    }
}
