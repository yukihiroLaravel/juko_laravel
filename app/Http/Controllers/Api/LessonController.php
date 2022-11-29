<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LessonGetRequest;
use App\Http\Resources\LessonGetResponse;
use App\Model\Attendance;


class LessonController extends Controller
{
  public function index(LessonGetRequest $request) {
    $attendance = Attendance::with(['course.chapter.lesson.lessonAttendance'])
    ->where('id', $request->attendance_id)->first();
    /* return response()->json($attendance->course->chapter); */
    $data = null;

    foreach ($attendance->course->chapter as $chapter) {
      if ($chapter->id === (int)$request->chapter_id) {
         /*  $data = $chapter; */
        $results = null;
      if (is_null($chapter) === false) {
          $results = [
              'chapter_id' => $chapter->id,
              'title' => $chapter->title,
          ];
        }
        foreach ($chapter->lesson as $key =>  $lesson) {
           /*  return response()->json($lesson); */
            $results['lessons'][] = [
              'lesson_id' => $lesson->id,
              'title' => $lesson->title,
              'url' => $lesson->url,
              'remarks' => $lesson->remarks,
              'lesson_attendance' => $lesson->lesson_attendance
          ];
          /* return response()->json($lesson->lesson_attendance); */
          /* foreach ($lesson->lesson_attendance as $lessonAttendances) {
            return response()->json($lessonAttendances);
            $results['lessons_attendances'][] = [
              'lesson_attendance_id' => $lessonAttendances->lesson_attendance,
              'status' => $lessonAttendances->status
            ];
          } */
        }
        /* return response()->json($results);   */
        /* return response()->json($chapter); */
      }
    }  
  } 
}
  