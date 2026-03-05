<?php

namespace App\Http\Controllers\Api\Student;

use App\Dto\Student\Attendance\IndexDto;
use App\Dto\Student\Attendance\ShowDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\Attendance\CompleteAllChaptersRequest;
use App\Http\Requests\Student\Attendance\CompleteAllLessonsRequest;
use App\Http\Requests\Student\Attendance\IndexRequest;
use App\Http\Requests\Student\Attendance\ProgressRequest;
use App\Http\Requests\Student\Attendance\ShowRequest;
use App\Http\Resources\Student\AttendanceCourseProgressResource;
use App\Http\Resources\Student\AttendanceIndexResource;
use App\Http\Resources\Student\AttendanceShowResource;
use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\LessonAttendance;
use App\Services\Student\Attendance\IndexService;
use App\Services\Student\Attendance\ShowService;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @tags Student-Attendance
 */
class AttendanceController extends Controller
{
        /**
     * 受講一覧取得API
     */
    public function index(
    IndexRequest $request,
    IndexService $service
    ): ResourceCollection { 
        $studentId = Auth::id();
        $results = $service(
            indexDto: new IndexDto($studentId, $request->search_word),
            perPage: $request->input('per_page', 6),
            page: $request->input('page', 1),
            tagId: $request->input('tag_id')
        );

        $results->getCollection()->transform(function ($attendance) {
        $attendance->continue_from = $this->getContinueFrom($attendance);
        return $attendance;
        });

        return AttendanceIndexResource::collection($results);
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
     * 受講講座の進捗情報を取得
     */
    public function progress(ProgressRequest $request): AttendanceCourseProgressResource
    {
        $attendance = Attendance::with([
            'course.chapters.lessons',
            'lessonAttendances',
        ])
            ->findOrFail($request->attendance_id);

        $this->authorize('viewStudent', $attendance);

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
     * 全レッスン完了
     */
    public function completeAllLessons(CompleteAllLessonsRequest $request): JsonResponse
    {
        // 受講レコードを取得
        $attendance = Attendance::findOrFail($request->attendance_id);

        $this->authorize('update', $attendance);

        // 該当チャプターを取得
        $chapter = Chapter::with('lessons')->findOrFail($request->chapter_id);

        // $chapter が $attendance に紐づくか確認
        if ($chapter->course_id !== $attendance->course_id) {
            throw new AuthorizationException('Forbidden, invalid chapter.');
        }

        try {
            // 該当チャプターに含まれる全レッスンの受講状況を更新
            LessonAttendance::whereIn('lesson_id', $chapter->lessons->pluck('id'))
                ->where('attendance_id', $attendance->id)
                ->update([
                    'status' => LessonAttendance::STATUS_COMPLETED_ATTENDANCE,
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
     * 全チャプター完了API
     */
    public function completeAllChapters(CompleteAllChaptersRequest $request): JsonResponse
    {
        $attendance = Attendance::findOrFail($request->attendance_id);

        // 本人のみ更新可
        $this->authorize('update', $attendance);

        $lessonAttendanceIds = LessonAttendance::where('attendance_id', $attendance->id)
            ->pluck('id')
            ->toArray();

        LessonAttendance::whereIn('id', $lessonAttendanceIds)
            ->update(['status' => LessonAttendance::STATUS_COMPLETED_ATTENDANCE]);

        return response()->json([
            'result' => true,
        ]);
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
        return $attendance->lessonAttendances->filter(fn ($lessonAttendance) => $lessonAttendance->status === LessonAttendance::STATUS_COMPLETED_ATTENDANCE)->count();
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

    /**
     * 続きのレッスンIDとタイトル、それを含むチャプターIDとタイトルを取得する
     *
     * @param  Attendance  $attendance
     * @return array | null
     */
    private function getContinueFrom($attendance)
    {
        foreach ($attendance->course->chapters as $chapter) {
            $incompleteLesson = $chapter->lessons->first(function ($lesson) use ($attendance) {
                $status = $attendance->lessonAttendances
                    ->where('lesson_id', $lesson->id)
                    ->first()?->status;

                return $status !== LessonAttendance::STATUS_COMPLETED_ATTENDANCE;
            });

            if ($incompleteLesson) {
                return [
                    'chapter_id'    => $chapter->id,
                    'chapter_title' => $chapter->title,
                    'lesson_id'     => $incompleteLesson->id,
                    'lesson_title'  => $incompleteLesson->title,
                ];
            };
        }
        
        return null;
    }
}
