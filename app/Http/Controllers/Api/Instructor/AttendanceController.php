<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Model\Attendance;
use App\Model\LessonAttendance;
use App\Model\Chapter;
use App\Http\Requests\Instructor\AttendanceShowRequest;
use App\Http\Resources\Instructor\AttendanceShowResource;

class AttendanceController extends Controller
{
    /**
     * 受講状況取得API
     *
     * @param AttendanceShowRequest $request
     * @return AttendanceShowResource
     */
    public function show(AttendanceShowRequest $request) {
        $courseId = $request->course_id;
        $chapterData = Chapter::with('lessons.lessonAttendances')->where('course_id', $courseId)->get();
        $studentsCount = Attendance::where('course_id', $courseId)->count();

        foreach ($chapterData as $chapter) {
            $completedCount = 0;
            foreach ($chapter->lessons as $lesson) {
                foreach ($lesson->lessonAttendances as $lessonAttendance) {
                    if($lessonAttendance->status === LessonAttendance::STATUS_COMPLETED_ATTENDANCE){
                        $completedCount+=1;
                    }
                }
            }
            $chapter->completedCount = $completedCount;
        }
        return new AttendanceShowResource([
            'chapterData' => $chapterData,
            'studentsCount' => $studentsCount,
        ]);
    }
}
