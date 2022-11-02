<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseGetRequest;
use App\Http\Resources\CourseGetResponse;
use App\Model\LessonAttendance;

class ChapterController extends Controller
{
    public function index(CourseGetRequest $request)
    {
        $lesson_attendances = LessonAttendance::with(['lesson.chapter.course'])->where('course_id', $request->course_id)->get();
        return new CourseGetResponse($lesson_attendances);
    }
}