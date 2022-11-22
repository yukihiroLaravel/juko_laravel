<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseGetRequest;
use App\Http\Resources\CourseGetResponse;
use App\Model\Course;
use App\Model\Attendance;

class ChapterController extends Controller
{
    public function index(CourseGetRequest $request)
    {
        $attendances = Attendance::with(['course.chapter.lesson.lesson_attendance','course.instructor'])
        ->where('id', $request->attendance_id)
        ->first();

        $result = [
            'course_id' => $attendances->course_id,
            'title' => $attendances->course->title,
            'image' => $attendances->course->image,
            'instructor' => [
                'instructor_id' => $attendances->course->instructor->id,
                'nick_name' => $attendances->course->instructor->nick_name,
                'last_name' => $attendances->course->instructor->last_name,
                'first_name' => $attendances->course->instructor->first_name,
                'email' => $attendances->course->instructor->email,
            ],
            'attendance' => [
                'attendance_id' => $attendances->id,
                'progress' => $attendances->id,
            ],
            'chapters' => [],
        ];

        foreach ($attendances->course->chapter as $chapter) {
            $lessons = [];
            foreach ($chapter->lesson as $lesson) {
                $lessonAttendances = [];
                foreach ($lesson->lesson_attendance as $lessonAttendance) {
                    $lessonAttendances[] = [
                        'lesson_attendance_id' => $lessonAttendance->id,
                        'status' => $lessonAttendance->status,
                    ];
                }
                $lessons[] = [
                    'lesson_id' => $lesson->id,
                    'title' => $lesson->title,
                    'url' => $lesson->url,
                    'remarks' => $lesson->remarks,
                    'lesson_attendances' => $lessonAttendances,
                ];
            }
            $result['chapters'][] = [
                'chapter_id' => $chapter->id,
                'title' => $chapter->title,
                'lessons' => $lessons,
            ];
        }

        return response()->json($result);
    }
}
