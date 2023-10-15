<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseProgressRequest;
use App\Http\Requests\CourseShowRequest;
use App\Http\Requests\CourseIndexRequest;
use App\Http\Resources\CourseIndexResource;
use App\Http\Resources\CourseShowResource;
use App\Http\Resources\CourseProgressResource;
use App\Model\Attendance;
use App\Model\Course;
use App\Model\LessonAttendance;
use App\Model\Chapter;

class CourseController extends Controller
{
    /**
     * 公開中の講座を抽出
     *
     * @param \Illuminate\Support\Collection $attendances
     * @return \Illuminate\Support\Collection
     */
    private function extractPublicCourse($attendances)
    {
        return $attendances->filter(function ($attendance) {
            return $attendance->course->status === Course::STATUS_PUBLIC;
        })
        ->values();
    }

    /**
     * 講座詳細取得API
     *
     * @param CourseShowRequest $request
     * @return CourseShowResource
     */
    public function show(CourseShowRequest $request)
    {
        $attendance = Attendance::with([
            'course.chapters.lessons',
            'course.instructor',
            'lessonAttendances'
        ])
        ->findOrFail($request->attendance_id);

        $publicChapters = Chapter::extractPublicChapter($attendance->course->chapters);
        $attendance->course->chapters = $publicChapters;
        return new CourseShowResource($attendance);
    }

    /**
     * チャプター進捗状況、続きのレッスンID取得API
     *
     * @param CourseProgressRequest $request
     * @return CourseProgressResource
     */
    public function progress(CourseProgressRequest $request)
    {
        // TODO 認証ユーザーを一時的にid=1とする。
        $authId = 1;
        $attendance = Attendance::with([
            'course.chapters.lessons',
            'lessonAttendances'
        ])
        ->where([
            'course_id' => $request->route('course_id'),
            'student_id' => $authId
        ])
        ->firstOrFail();

        $progressData = [
            'completedChaptersCount' => $this->getCompletedChaptersCount($attendance),
            'totalChaptersCount' => $this->getTotalChaptersCount($attendance),
            'completedLessonsCount' => $this->getCompletedLessonsCount($attendance),
            'totalLessonsCount' => $this->getTotalLessonsCount($attendance),
            'youngestUnCompletedLessonId' => $this->getYoungestUnCompletedLessonId($attendance)
        ];

        return new CourseProgressResource([
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
     * 続きのレッスンIDを取得する
     *
     * @param Attendance $attendance
     * @return int|null
     */
    private function getYoungestUnCompletedLessonId($attendance)
    {
        // IDが最も若い未完了のチャプターの内、IDが最も若い未完了のレッスン
        $youngestUnCompletedLessonId = null;
        $attendance->course->chapters->each(function ($chapter) use ($attendance, &$youngestUnCompletedLessonId) {
            $chapter->lessons->each(function ($lesson) use ($attendance, &$youngestUnCompletedLessonId) {
                $lessonAttendance = $attendance->lessonAttendances->where('lesson_id', $lesson->id)->first();

                if ($lessonAttendance->status !== LessonAttendance::STATUS_COMPLETED_ATTENDANCE) {
                    if ($youngestUnCompletedLessonId === null) {
                        $youngestUnCompletedLessonId = $lesson->id;
                        return;
                    }
                    if ($youngestUnCompletedLessonId > $lesson->id) {
                        $youngestUnCompletedLessonId = $lesson->id;
                    }
                }
            });
        });
        return $youngestUnCompletedLessonId;
    }
}
