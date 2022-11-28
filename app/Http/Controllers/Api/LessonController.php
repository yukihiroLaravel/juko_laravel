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
   
    $data = null;

    foreach ($attendance->course->chapter as $chapter) {
      if ($chapter->id === (int)$request->chapter_id) {
          $data = $chapter;
      }
    
      $results = null;
      if (is_null($data) === false) {
          $results = [
              'chapter_id' => $data->id,
              'title' => $data->title,
          ];
      }
        foreach ($data->lesson as $lesson) {
          $results['lessons'][] = [
            'lesson_id' => $lesson->id,
            'title' => $lesson->title,
            'url' => $lesson->url,
            'remarks' => $lesson->remarks,
            'lessonattendance' => $lesson->lesson_attendance
          ];
          foreach ((array)$lesson->lesson_attendance as $lessonAttendances) {
            $results['lessons_attendances'][] = [
              'lesson_attendance_id' => $lessonAttendances->lesson_attendance,
              'status' => $lessonAttendances->status
            ];
          }
        }
    }
    return response()->json($lesson);
  }
}
  