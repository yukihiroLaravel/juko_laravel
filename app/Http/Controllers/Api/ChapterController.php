<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChapterGetRequest;
use App\Model\Chapter;
use App\Model\Attendance;



class ChapterController extends Controller
{
    public function index(ChapterGetRequest $request)
    {
        $attendance = Attendance::with(['course.chapter.lesson', 'student', 'lessonAttendances'])
        ->where('id', $request->lessonAttendance_id)
        ->get();

        return response()->json($attendance);
        
    }
}
