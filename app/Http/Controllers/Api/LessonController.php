<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LessonGetRequest;
use App\Model\Attendance;

class LessonController extends Controller
{
    public function index(LessonGetRequest $request)
    {
        $attendance = Attendance::with([
                'course.chapter.lesson',
                'lessonAttendance'
            ])
            ->where('id', $request->attendance_id)
            ->first();

        $results = null;
        foreach ($attendance->course->chapter as $chapter) {
            if ($chapter->id === (int)$request->chapter_id) {
                if (is_null($chapter) === false) {
                    $results = [
                        'chapter_id' => $chapter->id,
                        'title' => $chapter->title,
                    ];
                }
                foreach ($chapter->lesson as $key => $lesson) {
                    $newLessonAttendance = null;
                    foreach ($attendance->lessonAttendance as $lessonAttendance) {
                        if ($lesson->id == $lessonAttendance->lesson_id) {
                                $newLessonAttendance = [
                                    'lesson_attendance_id' => $lessonAttendance->id,
                                    'status' => $lessonAttendance->status
                                ];
                        }
                    }
                    $results['lessons'][] = [
                        'lesson_id' => $lesson->id,
                        'title' => $lesson->title,
                        'url' => $lesson->url,
                        'remarks' => $lesson->remarks,
                        'lesson_attendance' => $newLessonAttendance
                    ];
                }
            }
        }
        return response()->json(['data' => $results]);
    }
}
