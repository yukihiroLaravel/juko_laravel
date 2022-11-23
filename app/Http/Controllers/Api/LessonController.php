<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LessonGetRequest;
use App\Http\Resources\LessonGetResponse;
use App\Model\Attendane;


class LessonController extends Controller
{
    public function index(LessonGetRequest $request)
    {
      $attendance = Attendance::with(['course.chapter.lesson', 'student', 'lessonAttendanes'])
      ->where('id', $request->attendance_id)->first();
        $result = [
          'chapter_id' => $attendance->id,
          'title' => $attendance->title,
          'lessons' => $attendance->lesson
        ];
        return response()->json($result);
        /* foreach($attendance->lesson as $lesson){
          $result = [
            'chapter_id' => $lesson->chapter_id,
            'title' => $lesson->chapter->title,
              'lesson' => [
                'lesson_id' => $lesson->lessonAttendance->lesson_id
                
              ],
          ];
          return response()->json($result);

          } */
          
    }
        
}
