<?php

namespace App\Http\Controllers\Api\Student;

use App\Model\Chapter;
use App\Model\Attendance;
use App\Model\LessonAttendance;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\Attendance\QueryService;
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
     * 受講一覧取得API
     * 
     * @param AttendanceIndexRequest $request
     * @param QueryService $queryService
     * @return AttendanceIndexResource
     */
    public function index(AttendanceIndexRequest $request, QueryService $queryService)
    {
        $studentId = Auth::id();

        $attendances = $queryService->getAttendancesByStudentIdAndSearchWords($studentId, $request->search_word);

        return new AttendanceIndexResource($attendances);
    }

    /**
     * 受講詳細取得API
     *
     * @param AttendanceShowRequest $request
     * @param QueryService $queryService
     * @return AttendanceShowResource|\Illuminate\Http\JsonResponse
     */
    public function show(AttendanceShowRequest $request, QueryService $queryService)
    {
        $attendance = $queryService->getAttendanceById($request->attendance_id);
        
        //ログインユーザ本人の場合のみリクエストを返す
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
     * @param QueryService $queryService
     * @return AttendanceShowChapterResource
     */
    public function showChapter(AttendanceShowChapterRequest $request, QueryService $queryService)
    {
        $attendance = $queryService->getAttendanceById($request->attendance_id);

        //ログインユーザ本人の場合のみリクエストを返す
        if ($attendance->student_id !== $request->user()->id) {
            return response()->json([
                "result" => false,
                "message" => "Access forbidden."
            ], 403);
        }

        //存在しないコースIDでエラーを出す認可処理
        if ($attendance->course_id !== (int) $request->course_id) {
            return response()->json([
                "result" => false,
                "message" => "Forbidden, not allowed to this course."
            ], 403);
        }

        // 公開されているチャプターのみ抽出
        $publicChapters = Chapter::extractPublicChapter($attendance->course->chapters);
        $attendance->course->chapters = $publicChapters;

        // リクエストのチャプターIDと一致するチャプターのみ抽出
        $chapter = $queryService->getChapterByRequest($attendance, $request->chapter_id);

        return new AttendanceShowChapterResource([
            'attendance' => $attendance,
            'chapter' => $chapter,
        ]);
    }

    /**
     * 受講講座の進捗情報を取得
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
                            'chapter_id' => $chapter->id,
                            'lesson_id' => $lesson->id,
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
