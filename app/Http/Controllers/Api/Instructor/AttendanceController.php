<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Model\Attendance;
use App\Model\LessonAttendance;
use App\Model\Chapter;

class AttendanceController extends Controller
{
    /**
     * 受講状況取得API
     *
     * @param 
     * @return 
     */
    public function show($course_id) {
        $courseId = $course_id;
        $chapters = [];
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
            $chapters[] = [
                'chapter_id' => $chapter->id,
                'title' => $chapter->title,
                'completed_count' => $completedCount,
            ];
        }
        
        return response()->json([
            "chapters" => $chapters,
            "students_count" => $studentsCount,
        ]);
    }
}
