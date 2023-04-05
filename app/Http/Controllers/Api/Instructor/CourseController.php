<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Resources\Instructor\CoursesGetResponse;

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
}
