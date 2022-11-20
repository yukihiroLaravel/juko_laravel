<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LessonGetRequest;
use App\Http\Resources\LessonGetResponse;
use App\Model\Attendance;


class LessonController extends Controller
{
    public function index(LessonGetRequest $request)
    {
      $attendance = Attendance::with(['course.chapter.lesson', 'student', 'lessonAttendance'])
      ->where('id', $request->attendance_id)
      ->first();

      /* return response()->json($lesson);*/
       /* $result = [
        'title' => $lesson->title,
        'lessons' => []
      ];  */
      
       foreach($attendance->course->chapter as $chapter){
         if($chapter->id == $request->chapter_id){
             return response()->json($chapter);
         }
        /* $result['lesson'][] = [
        'chapter_id' => $chapter->id,
        'title' => $chapter->title,
        ]; */
       
      }
      
  }
      /* return new LessonGetResponse($lesson); */
}
