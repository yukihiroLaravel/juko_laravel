<?php

namespace App\Http\Controllers\Api\Student;

use App\Dto\Student\Attendance\IndexDto;
use App\Dto\Student\Attendance\ShowDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\Attendance\IndexRequest;
use App\Http\Requests\Student\Attendance\ProgressRequest;
use App\Http\Requests\Student\Attendance\ShowChapterRequest;
use App\Http\Requests\Student\Attendance\ShowRequest;
use App\Http\Resources\Student\AttendanceCourseProgressResource;
use App\Http\Resources\Student\AttendanceIndexResource;
use App\Http\Resources\Student\AttendanceShowChapterResource;
use App\Http\Resources\Student\AttendanceShowResource;
use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\LessonAttendance;
use App\Services\Student\Attendance\IndexService;
use App\Services\Student\Attendance\ShowService;
use Illuminate\Auth\Access\AuthorizationException;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    /**
     * 受講一覧取得API
     */
    public function index(
        IndexRequest $request,
        IndexService $service
    ): AttendanceIndexResource {
        $studentId = Auth::id();
        $indexDto = new IndexDto($studentId, $request->search_word);
        $attendances = $service($indexDto);

        return new AttendanceIndexResource($attendances);
    }

    /**
     * 受講詳細取得API
     */
    public function show(
        ShowRequest $request,
        ShowService $service
    ): AttendanceShowResource {
        try {
            $attendanceId = (int) $request->attendance_id;
            $userId = $request->user()->id;
            $showDto = new ShowDto($attendanceId, $userId);
            $attendance = $service($showDto);

            return new AttendanceShowResource($attendance);
        } catch (AuthorizationException $e) {
            Log::error($e->getMessage()."\n".$e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * チャプター詳細情報を取得
     *
     * @return AttendanceShowChapterResource
     */
    public function showChapter(ShowChapterRequest $request)
    {
        $attendance = Attendance::with([
            'course.chapters.lessons',
            'lessonAttendances',
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
     * 受講講座の進捗情報を取得
     *
     * @return AttendanceCourseProgressResource|\Illuminate\Http\JsonResponse
     */
    public function progress(ProgressRequest $request)
    {
        $authId = Auth::id();
        $attendance = Attendance::with([
            'course.chapters.lessons',
            'lessonAttendances',
        ])
            ->findOrFail($request->attendance_id);

        if ($authId !== $attendance->student_id) {
            // ログインしている生徒が受講しているコースではない
            throw new AuthorizationException('Not authorized.');
        }

        $progressData = [
            'completedChaptersCount' => $this->getCompletedChaptersCount($attendance),
            'totalChaptersCount' => $this->getTotalChaptersCount($attendance),
            'completedLessonsCount' => $this->getCompletedLessonsCount($attendance),
            'totalLessonsCount' => $this->getTotalLessonsCount($attendance),
            'youngestUnCompletedLesson' => $this->getYoungestUnCompletedLesson($attendance),
        ];

        return new AttendanceCourseProgressResource([
            'attendance' => $attendance,
            'progressData' => $progressData,
        ]);
    }

    /**
     * チャプター&レッスン一覧画面 全Lesson完了機能
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function completeAllLessons(Request $request, int $attendance_id, int $chapter_id)
    {
        // ログイン中の生徒ID
        $studentId = Auth::id();

        // 受講レコードと関連データ（コース、チャプター、レッスン、受講状況）を取得
        $attendance = Attendance::with([
            'course.chapters.lessons',
            'lessonAttendances'
        ])->findOrFail($attendance_id);
    
        // 認証チェック: この生徒が対象の受講レコードにアクセスできるか
        if ($attendance->student_id !== $studentId) {
            throw new AuthorizationException('Forbidden, invalid student.');
        }
    
        // 該当チャプターを取得
        $chapter = $attendance->course->chapters->firstWhere('id', $chapter_id);
        if (!$chapter) {
            throw new Exception('Forbidden, invalid chapter.');
        }
    
        try {
            // 該当チャプターに含まれる全レッスンの受講状況を更新
            LessonAttendance::whereIn('lesson_id', $chapter->lessons->pluck('id'))
                ->where('attendance_id', $attendance_id)
                ->update([
                    'status' => LessonAttendance::STATUS_COMPLETED_ATTENDANCE
                ]);

            return response()->json([
                'result' => true,
            ]);
        } catch (Exception $e) {
            Log::error($e);
            throw $e;
        }
    }

    /**
     * 完了済みのチャプター数を取得する
     *
     * @param  Attendance  $attendance
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
     * @param  Attendance  $attendance
     * @return int
     */
    private function getTotalChaptersCount($attendance)
    {
        return $attendance->course->chapters->count();
    }

    /**
     * 完了済みのレッスン数を取得する
     *
     * @param  Attendance  $attendance
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
     * @param  Attendance  $attendance
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
     * @param  Attendance  $attendance
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
