<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;    //postman確認のため仮作成
use App\Http\Requests\CourseShowRequest;
use App\Http\Requests\CourseIndexRequest;
use App\Http\Resources\CourseIndexResource;
use App\Http\Resources\CourseShowResource;
use App\Model\Attendance;
use App\Model\Course;
use App\Model\Chapter;
use App\Model\LessonAttendance;

class CourseController extends Controller
{
    /**
     * 講座一覧取得API
     *
     * @param CourseIndexRequest $request
     * @return CourseIndexResource
     */
    public function index(CourseIndexRequest $request)
    {
        if ($request->text === null) {
            $attendances = Attendance::with(['course.instructor'])->where('student_id', $request->student_id)->get();
            $publicAttendances = $this->extractPublicCourse($attendances);
            return new CourseIndexResource($publicAttendances);
        }

        $attendances = Attendance::whereHas('course', function ($q) use ($request) {
            $q->where('title', 'like', "%$request->text%");
        })
            ->with(['course.instructor'])
            ->where('student_id', '=', $request->student_id)
            ->get();
        $publicAttendances = $this->extractPublicCourse($attendances);
        return new CourseIndexResource($publicAttendances);
    }

    private function extractPublicCourse($attendances)
    {
        return $attendances->filter(function ($attendance) {
            return $attendance->course->status === Course::STATUS_PUBLIC;
        });
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

        return new CourseShowResource($attendance);
    }
    /**
     * チャプター進捗状況、続きのレッスンID取得API
     *
     * @param Request $request
     * @return Resource
     */
    public function progress(Request $request)
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

        // 終了済みのチャプター数
        $completedLessonAttendanceIds = $attendance->lessonAttendances->filter(function ($lessonAttendance) {
            return $lessonAttendance->status === LessonAttendance::STATUS_COMPLETED_ATTENDANCE;
        })
        ->pluck('id');
        
        $groupedLessons = $attendance->course->chapters
        ->flatMap(function ($chapter) {
            return $chapter->lessons->groupBy('chapter_id');
        });

        $completedChaptersCount = $groupedLessons->filter(function ($groupedLesson) use ($completedLessonAttendanceIds) {
            return $groupedLesson->pluck('id')->intersect($completedLessonAttendanceIds)->count() === $groupedLesson->count();
        })->count();

        // チャプター合計
        $totalChaptersCount = $attendance->course->chapters->count();

        // 終了済みのレッスン数
        $completedLessonsCount = 0;
        foreach ($attendance->lessonAttendances as $lessonAttendance)
        if ($lessonAttendance->status === LessonAttendance::STATUS_COMPLETED_ATTENDANCE) {
            $completedLessonsCount ++;
        }
        
        // レッスン合計
        $totalLessonsCount = 0;
        foreach ($attendance->course->chapters as $chapter) {
            $lessonCount = $chapter->lessons->count();
            $totalLessonsCount += $lessonCount; 
        }

        // 未完了のチャプターでIDが最も若いレッスン（続きのレッスンID取得）
        $UnCompletedLessonAttendanceIds = $attendance->lessonAttendances->filter(function ($lessonAttendance) {
            return $lessonAttendance->status !== LessonAttendance::STATUS_COMPLETED_ATTENDANCE;
        })
        ->pluck('id');
        
        $youngestUnCompletedLesson = $attendance->course->chapters
        ->flatMap(function ($chapter) use ($UnCompletedLessonAttendanceIds) {
            return $chapter->lessons->filter(function ($lesson) use ($UnCompletedLessonAttendanceIds) {
                return $UnCompletedLessonAttendanceIds->contains($lesson->id);
            });
        })
        ->sortBy('chapter_id')->first()['id'];

        return response()->json([
            'course' =>[
                'course_id' => $request->course_id,
                'progress' => $attendance->progress
            ],
            "number_of_completed_chapters" => $completedChaptersCount,
            "number_of_total_chapters" => $totalChaptersCount,
            "number_of_completed_lessons" => $completedLessonsCount,
            "number_of_total_lessons" => $totalLessonsCount,
            "continue_lesson_id" => $youngestUnCompletedLesson
        ]); 
    }
}
