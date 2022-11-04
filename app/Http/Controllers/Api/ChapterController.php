<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseGetRequest;
use App\Http\Resources\CourseGetResponse;
use App\Model\Course;

class ChapterController extends Controller
{
    public function index(CourseGetRequest $request)
    {
        $courses = Course::with('chapter')->where('id', '=', $request->course_id)->get();
        foreach ($courses as $key => $course) {
            // dd($course);
            // \Log::debug($course);
            return response()->json($course);
        }
    }
}