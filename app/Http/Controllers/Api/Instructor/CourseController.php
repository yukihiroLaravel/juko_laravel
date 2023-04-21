<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\CourseGetRequest;
use App\Http\Resources\Instructor\CoursesGetResponse;
use App\Http\Resources\Instructor\CourseGetResponse;
use App\Model\Course;

class CourseController extends Controller
{
    /**
     * 講師側講座一覧取得API
     *
     * @param $instructor_id
     * @return CoursesGetResponse
     */
    public function index($instructor_id)
    {
        return new CoursesGetResponse([]);
    }
    
    /**
     * 講師側講座取得API
     *
     * @param CourseGetRequest $request
     * @return CourseGetResponse
     */
    public function show(CourseGetRequest $request)
    {
        $course = Course::with(['chapters.lessons'])->where('id',$request->course_id)->first();
        return new CourseGetResponse($course);
    }
}
