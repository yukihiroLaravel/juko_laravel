<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Model\Attendance;
use App\Model\Chapter;

class AttendanceController extends Controller
{
    public function show() {
        $courseId = 1;
        $chapters = [];
        $chapterData = Chapter::where('course_id', $courseId)
            ->with(['lessons' => function ($query) {
                $query->with('lessonAttendance');
            }])
            ->get();
        $studentsCount = Attendance::where('course_id', $courseId)->count();

        foreach ($chapterData as $chapter) {
            $completedCount = 0;
            $chapterId = $chapter->id;
            $title = $chapter->title;
            $chapterInfo = ['chapter_id' => $chapterId, 'title' => $title];
            foreach ($chapter->lessons as $lesson) {
                foreach ($lesson->lessonAttendance as $lessonAttendance) {
                    $status = $lessonAttendance->status;
                    if($status === 'completed_attendance'){
                        $completedCount+=1;
                    }
                }
            }
            $chapterInfo['completed_count'] = empty($chapter->lessons) ? 0 : $completedCount;
            $chapters[] = $chapterInfo;
        }
        
        return response()->json([
            "chapters" => $chapters,
            "students_count" => $studentsCount,
        ]);
    }
}
