<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChapterGetRequest;
use App\Model\Attendance;



class ChapterController extends Controller
{
    public function index(ChapterGetRequest $request)
    {
        $course = Attendance::with(['course.chapter.lesson', 'student', 'lessonAttendance'])
        ->where('id', $request->course_id)
        ->first();
        
        /* return response()->json($attendance); */
        $result = [
            'course_id' => $course->course_id,
            'title' => $course->course->title,
            'chapters' => [],
            'lessons' => []
        ];

        foreach($course->course->chapter as $chapter){
                $result['chapters'][] = [
                'chapter_id' => $chapter->id,
                'title' => $chapter->title,
            ];
        }
        return response()->json($result);
    }
}
