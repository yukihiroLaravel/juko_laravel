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
        $courses = Course::with(['chapter.lesson.lesson_attendance'])->where('id', $request->course_id)->get();
        // $attendances = Attendance::where('id', $request->course_id)->get();
        
        foreach ($courses as $key => $course) {
                    return response()->json($courses);
                }
            // return response()->json([$courses,$attendances]);
    }
}