<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseGetRequest;
use App\Model\Attendance;

class ChapterController extends Controller
{
    public function index(CourseGetRequest $request)
    {
        $attendances = Attendance::with([
            'course.chapter.lesson',
            'course.instructor',
            'lessonAttendances'
            ])
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
                'progress' => $attendances->progress,
            ],
            'chapters' => [],
        ];

        foreach ($attendances->course->chapter as $chapter) {
            $lessons = [];
            foreach ($chapter->lesson as $lesson) {
                $newLessonAttendance = null;
                foreach ($attendances->lessonAttendances as $lessonAttendance) {
                    if ($lesson->id == $lessonAttendance->lesson_id) {
                        $newLessonAttendance = [
                            'lesson_attendance_id' => $lessonAttendance->id,
                            'status' => $lessonAttendance->status,
                        ];
                    }
                }
                $lessons[] = [
                    'lesson_id' => $lesson->id,
                    'title' => $lesson->title,
                    'url' => $lesson->url,
                    'remarks' => $lesson->remarks,
                    'lesson_attendance' => $newLessonAttendance,
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
