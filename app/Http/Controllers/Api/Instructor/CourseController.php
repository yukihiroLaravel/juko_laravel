<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Model\Attendance;

class CourseController extends Controller
{
    /**
     * 講座一覧取得API
     *
     * @param $instructor_id
     * @return array
     */
    public function index($instructor_id)
    {
        return [];
    }

}
