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
            'course.chapters.lessons.lessonAttendance'
        ])
        ->where([
            'course_id' => $request->route('course_id'),
            'student_id' => $authId
        ])
        ->first();

        
        // 終了済みのレッスン数
        $completedLessonsCount = 0;
        foreach ($attendance->course->chapters as $chapter) {
            foreach ($chapter->lessons as $lesson) {
                $completedAttendancesCount = $lesson->lessonAttendance->where('status', 'completed_attendance')->count();
                $completedLessonsCount += $completedAttendancesCount;
            }
        }

        // 終了済みのチャプター数
        $completedChaptersCount = 0;
        foreach ($attendance->course->chapters as $chapter) {
            if (count($chapter->lessons) === 0 ) {
                continue;
            }
            if ($this->getChapterProgress($chapter->lessons)) {
                $completedChaptersCount++;
            }
        }

        // チャプター・チャプター毎のレッスン数
        $chapterLessons = Chapter::where('course_id', $request->route('course_id'))
            ->withCount('lessons')
            ->get();

        // 最もIDが若い未完了のチャプター（続きのレッスンID取得）
        $youngestUnCompletedChapter = null;
        foreach ($attendance->course->chapters as $chapter) {
            if (!$this->getChapterProgress($chapter->lessons)) {
                if ($youngestUnCompletedChapter === null || $chapter->id < $youngestUnCompletedChapter) {
                    $youngestUnCompletedChapter = $chapter->id;
                }
            }
        }

        return response()->json([
            'course' =>[
                'course_id' => $request->course_id,
                'progress' => $attendance->progress
            ],
            "number_of_completed_chapters" => $completedChaptersCount,
            "number_of_total_chapters" => $chapterLessons->count(),
            "number_of_completed_lessons" => $completedLessonsCount,
            "number_of_total_lessons" => $chapterLessons->sum('lessons_count'),
            "continue_lesson_id" => $youngestUnCompletedChapter
        ]); 
    }

    private function getChapterProgress($lessons) {
        foreach ($lessons as $lesson) {
            foreach ($lesson->lessonAttendance as $lessonAttendance) {
                if ($lessonAttendance->status !== 'completed_attendance') {
                    return false;
                }
            }
        }
        return true;
    }
}
