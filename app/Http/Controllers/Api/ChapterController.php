<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseGetRequest;
use App\Http\Resources\CourseGetResponse;
use App\Model\Course;
use App\Model\Attendance;

class ChapterController extends Controller
{
    public function index(CourseGetRequest $request)
    {
        // $attendances = Attendance::where('id', $request->course_id)->get();
        $attendances = Attendance::with(['course.chapter.lesson.lesson_attendance'])
        ->where('id',$request->attendance_id)
        ->get();
        
        // foreach ($attendances as $key => $attendance) {
        //             return response()->json($attendances);
        //         }
            return response()->json($attendances);
    }
}