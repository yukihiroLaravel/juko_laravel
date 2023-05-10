<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Resources\Instructor\CoursesGetResponse;
use App\Http\Requests\Instructor\CoursesGetRequest;
use App\Http\Requests\Instructor\CourseDeleteRequest;
use App\Model\Course;

class CourseController extends Controller
{    
    /**
     * 講師側講座一覧取得API
     *
     * @param CoursesGetRequest $request
     * @return CoursesGetResponse
     */
    public function index(CoursesGetRequest $request)
    {
        $courses = Course::where('instructor_id', $request->instructor_id)->get();

        return new CoursesGetResponse($courses);
    }

    /**
     * 講師側講座削除
     *
     * @param CourseDeleteRequest $request
     */
    public function destroy (CourseDeleteRequest $request)
    {   
        $course = Course::findOrfail($request->course_id);

        $course->delete();

        return response()->json([

        ]);
    }    
}
