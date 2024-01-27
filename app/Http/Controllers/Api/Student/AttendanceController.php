<?php

namespace App\Http\Controllers\Api\Student;

use App\Model\Course;
use App\Model\Chapter;
use App\Model\Attendance;
use App\Model\LessonAttendance;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\Student\AttendanceShowRequest;
use App\Http\Requests\Student\AttendanceIndexRequest;
use App\Http\Resources\Student\AttendanceShowResource;
use App\Http\Resources\Student\AttendanceIndexResource;
use App\Http\Requests\Student\AttendanceShowChapterRequest;
use App\Http\Resources\Student\AttendanceShowChapterResource;
use App\Http\Requests\Student\AttendanceCourseProgressRequest;
use App\Http\Resources\Student\AttendanceCourseProgressResource;

class AttendanceController extends Controller
{
    /**
     * 受講中講座一覧取得API
     *
     * @param AttendanceIndexRequest $request
     * @return AttendanceIndexResource
     */
    public function index(AttendanceIndexRequest $request)
    {
        $studentId = Auth::id();

        if (!$request->search_word) {
            $attendances = Attendance::with('course.instructor')
            ->where('student_id', $studentId)
            ->whereHas('course', function (Builder $query) {
                $query->where('status', Course::STATUS_PUBLIC);
            })->get();
            return new AttendanceIndexResource($attendances);
        }

        $attendances = Attendance::with('course.instructor')
        ->where('student_id', $studentId)
        ->whereHas('course', function (Builder $query) use ($request) {
            $query->where('title', 'like', "%{$request->search_word}%");
            $query->where('status', Course::STATUS_PUBLIC);
        })->get();

        return new AttendanceIndexResource($attendances);
    }

    /**
     * 講座詳細取得API
     *
     * @param AttendanceShowRequest $request
     * @return AttendanceShowResource|\Illuminate\Http\JsonResponse
     */
    public function show(AttendanceShowRequest $request)
    {
        $attendance = Attendance::with([
            'course.chapters.lessons',
            'course.instructor',
            'lessonAttendances'
        ])
        ->findOrFail($request->attendance_id);

        if ($attendance->student_id !== $request->user()->id) {
            return response()->json([
                "result" => false,
                "message" => "Access forbidden."
            ], 403);
        }

        $publicChapters = Chapter::extractPublicChapter($attendance->course->chapters);
        $attendance->course->chapters = $publicChapters;
        return new AttendanceShowResource($attendance);
    }

    /**
     * チャプター詳細情報を取得
     *
     * @param AttendanceShowChapterRequest $request
     * @return AttendanceShowChapterResource
     */
    public function showChapter(AttendanceShowChapterRequest $request)
    {
        $attendance = Attendance::with([
                'course.chapters.lessons',
                'lessonAttendances'
            ])
            ->where('id', $request->attendance_id)
            ->firstOrFail();

        // 公開されているチャプターのみ抽出
        $publicChapters = Chapter::extractPublicChapter($attendance->course->chapters);
        $attendance->course->chapters = $publicChapters;

        // リクエストのチャプターIDと一致するチャプターのみ抽出
        $chapter = $attendance->course->chapters->filter(function ($chapter) use ($request) {
                return $chapter->id === (int) $request->chapter_id;
        })
            ->first();

        return new AttendanceShowChapterResource([
            'attendance' => $attendance,
            'chapter' => $chapter,
        ]);
    }

    /**
     * チャプター進捗状況、続きのレッスンID取得API
     *
     * @param AttendanceCourseProgressRequest $request
     * @return AttendanceCourseProgressResource|\Illuminate\Http\JsonResponse
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
            if ($youngestUnCompletedLesson['lesson_id'] !== null) {
                return;
            }

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
        });
        if ($youngestUnCompletedLesson['lesson_id'] === null) {
            return null;
        }
        return $youngestUnCompletedLesson;
    }
}
