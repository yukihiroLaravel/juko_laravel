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
use App\Http\Requests\Student\Attendance\StuckPointsRequest;
use App\Http\Resources\Common\Attendance\StuckPointsResource;
use App\Http\Resources\Student\AttendanceCourseProgressResource;
use App\Http\Resources\Student\AttendanceIndexResource;
use App\Http\Resources\Student\AttendanceShowResource;
use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\LessonAttendance;
use App\Services\Attendance\StuckPointsService;
use App\Services\Student\Attendance\ContinueFromService;
use App\Services\Student\Attendance\IndexService;
use App\Services\Student\Attendance\ShowService;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;

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
        IndexService $service,
        ContinueFromService $continueFromService
    ): AnonymousResourceCollection {
        $userId = $request->user()->id;
        $results = $service(
            indexDto: new IndexDto($userId, $request->search_word),
            perPage: $request->input('per_page', 6),
            page: $request->input('page', 1),
            tagId: $request->input('tag_id')
        );

        $continueFromMap = [];
        foreach ($results as $attendance) {
            $continueFromMap[$attendance->id] = $continueFromService($attendance);
        }

        AttendanceIndexResource::withContinueFromMap($continueFromMap);

        return AttendanceIndexResource::collection($results);
    }

    /**
     * 受講詳細取得API
     */
    public function show(
        ShowRequest $request,
        ShowService $service
    ): AttendanceShowResource {
        $attendance = $service(new ShowDto((int) $request->attendance_id));

        $this->authorize('viewStudent', $attendance);

        return new AttendanceShowResource($attendance);
    }

    /**
     * 受講講座の進捗情報を取得
     */
    public function progress(
        ProgressRequest $request,
        ContinueFromService $continueFromService
    ): AttendanceCourseProgressResource {
        $attendance = Attendance::with([
            'course.chapters.lessons',
            'lessonAttendances',
        ])
            ->findOrFail($request->attendance_id);

        $this->authorize('viewStudent', $attendance);

        $progressData = [
            'completedChaptersCount' => $this->getCompletedChaptersCount($attendance),
            'totalChaptersCount' => $attendance->course->chapters->count(),
            'completedLessonsCount' => $attendance
                ->lessonAttendances
                ->filter(fn ($lessonAttendance) => $lessonAttendance->status === LessonAttendance::STATUS_COMPLETED_ATTENDANCE)
                ->count(),
            'totalLessonsCount' => $this->getTotalLessonsCount($attendance),
            'continueFrom' => $continueFromService($attendance)?->toArray(),
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
     * つまずき注意箇所取得API
     */
    public function stuckPoints(StuckPointsRequest $request, StuckPointsService $service): AnonymousResourceCollection
    {
        // Policyによる認可チェック
        $attendance = Attendance::findOrFail($request->attendance_id);
        $this->authorize('viewStudent', $attendance);

        $result = $service($attendance->course_id);

        return StuckPointsResource::collection($result);
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
}
