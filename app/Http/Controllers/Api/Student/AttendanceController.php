<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\AttendanceShowRequest;
use App\Http\Resources\Student\AttendanceShowResource;
use App\Model\Attendance;
use App\Model\Course;
use App\Model\LessonAttendance;
use App\Model\Chapter;

class AttendanceController extends Controller
{
    /**
     * 講座詳細取得API
     *
     * @param AttendanceShowRequest $request
     * @return AttendanceShowResource
     */
    public function show(AttendanceShowRequest $request)
    {
        $attendance = Attendance::with([
            'course.chapters.lessons',
            'course.instructor',
            'lessonAttendances'
        ])
        ->findOrFail($request->attendance_id);

        $publicChapters = Chapter::extractPublicChapter($attendance->course->chapters);
        $attendance->course->chapters = $publicChapters;
        return new AttendanceShowResource($attendance);
    }
}
