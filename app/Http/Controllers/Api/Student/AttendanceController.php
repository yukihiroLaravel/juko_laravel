<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\Attendance;
use App\Http\Requests\Student\AttendanceCourseProgressRequest;
use App\Http\Resources\Student\AttendanceCourseProgressResource;
use Illuminate\Support\Facades\Auth;
use App\Model\LessonAttendance;

class AttendanceController extends Controller
{
    /**
     * チャプター進捗状況、続きのレッスンID取得API
     *
     * @param AttendanceCourseProgressRequest $request
     * @return AttendanceCourseProgressResource
     */
    public function progress(AttendanceCourseProgressRequest $request)
    {
        $authId = Auth::id();
        $attendance = Attendance::with([
            'course.chapters.lessons',
            'lessonAttendances'
        ])
        ->findOrFail($request->attendance_id);

        if ($authId !== $attendance->student_id) {
            return response()->json([
                'result' => false,
                'error_message' => 'Not authorized.'
            ], 403);
        }

        $progressData = [
            'completedChaptersCount' => $this->getCompletedChaptersCount($attendance),
            'totalChaptersCount' => $this->getTotalChaptersCount($attendance),
            'completedLessonsCount' => $this->getCompletedLessonsCount($attendance),
            'totalLessonsCount' => $this->getTotalLessonsCount($attendance),
            'youngestUnCompletedLesson' => $this->getYoungestUnCompletedLesson($attendance)
        ];

        return new AttendanceCourseProgressResource([
            'attendance' => $attendance,
            'progressData' => $progressData,
        ]);
    }

    /**
     * 完了済みのチャプター数を取得する
     *
     * @param Attendance $attendance
     * @return int
     */
    private function getCompletedChaptersCount($attendance)
    {
        return $attendance->course->chapters->filter(function ($chapter) use ($attendance) {
            $isCompleted = false;
            // 全てのレッスンが完了済みかどうかをチェック
            $chapter->lessons->each(function ($lesson) use ($attendance, &$isCompleted) {
                $lessonAttendance = $attendance->lessonAttendances->where('lesson_id', $lesson->id)->first();
                if ($lessonAttendance->status !== LessonAttendance::STATUS_COMPLETED_ATTENDANCE) {
                    $isCompleted = false;
                    return false;
                }
                $isCompleted = true;
            });
            return $isCompleted;
        })->count();
    }

    /**
     * チャプター合計を取得する
     *
     * @param Attendance $attendance
     * @return int
     */
    private function getTotalChaptersCount($attendance)
    {
        return $attendance->course->chapters->count();
    }

    /**
     * 完了済みのレッスン数を取得する
     *
     * @param Attendance $attendance
     * @return int
     */
    private function getCompletedLessonsCount($attendance)
    {
        return $attendance->lessonAttendances->filter(function ($lessonAttendance) {
            return $lessonAttendance->status === LessonAttendance::STATUS_COMPLETED_ATTENDANCE;
        })->count();
    }

    /**
     * レッスン合計を取得する
     *
     * @param Attendance $attendance
     * @return int
     */
    private function getTotalLessonsCount($attendance)
    {
        $totalLessonsCount = 0;
        foreach ($attendance->course->chapters as $chapter) {
            $lessonCount = $chapter->lessons->count();
            $totalLessonsCount += $lessonCount;
        }
        return $totalLessonsCount;
    }

    /**
     * 続きのレッスンIDと、それを含むチャプターのIDを取得する
     *
     * @param Attendance $attendance
     * @return array | null
     */
    private function getYoungestUnCompletedLesson($attendance)
    {
        // IDが最も若い未完了のチャプターの内、IDが最も若い未完了のレッスン
        $youngestUnCompletedLesson = [
            'chapter_id' => null,
            'lesson_id' => null,
        ];
        $attendance->course->chapters->each(function ($chapter) use ($attendance, &$youngestUnCompletedLesson) {
            $chapter->lessons->each(function ($lesson) use ($attendance, &$youngestUnCompletedLesson, $chapter) {
                $lessonAttendance = $attendance->lessonAttendances->where('lesson_id', $lesson->id)->first();
                if ($lessonAttendance->status !== LessonAttendance::STATUS_COMPLETED_ATTENDANCE) {
                    if ($youngestUnCompletedLesson['lesson_id'] === null) {
                        $youngestUnCompletedLesson = [
                            'lesson_id' => $lesson->id,
                            'chapter_id' => $chapter->id,
                        ];
                        return;
                    }
                }
            });
            if ($youngestUnCompletedLesson['lesson_id'] !== null) {
                return;
            }
        });
        if ($youngestUnCompletedLesson['lesson_id'] === null) {
            return null;
        }
        return $youngestUnCompletedLesson;
    }
}