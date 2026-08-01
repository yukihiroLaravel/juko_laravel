<?php

namespace App\Http\Controllers\Api\Student;

use App\Dto\Student\Attendance\IndexDto;
use App\Dto\Student\Attendance\ShowDto;
use App\Enums\Chapter\StatusEnum as ChapterStatusEnum;
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
use App\Model\Lesson;
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
            'course.publicChapters.publicLessons',
            'lessonAttendances',
        ])
            ->findOrFail($request->attendance_id);

        $this->authorize('viewStudent', $attendance);

        $publicLessons = $attendance->course->publicChapters
            ->flatMap(fn (Chapter $chapter) => $chapter->publicLessons);

        $progressData = [
            'completedChaptersCount' => $this->getCompletedChaptersCount($attendance),
            'totalChaptersCount' => $attendance->course->publicChapters->count(),
            'completedLessonsCount' => $publicLessons
                ->filter(fn (Lesson $lesson) => $attendance->hasCompletedLesson($lesson))
                ->count(),
            'totalLessonsCount' => $publicLessons->count(),
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
        $chapter = Chapter::with('publicLessons')->findOrFail($request->chapter_id);

        // $chapter が $attendance に紐づく公開中のチャプターか確認
        if ($chapter->course_id !== $attendance->course_id || $chapter->status !== ChapterStatusEnum::PUBLIC) {
            throw new AuthorizationException('Forbidden, invalid chapter.');
        }

        try {
            // 該当チャプターに含まれる公開中の全レッスンの受講状況を更新
            $attendance->lessonAttendances()
                ->whereIn('lesson_id', $chapter->publicLessons->pluck('id'))
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
        $attendance = Attendance::with('course.publicChapters.publicLessons')
            ->findOrFail($request->attendance_id);

        // 本人のみ更新可
        $this->authorize('update', $attendance);

        // 公開中のチャプターに含まれる公開中のレッスンの受講状況を更新
        $publicLessonIds = $attendance->course->publicChapters
            ->flatMap(fn (Chapter $chapter) => $chapter->publicLessons->pluck('id'));

        $attendance->lessonAttendances()
            ->whereIn('lesson_id', $publicLessonIds)
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
     * 受講済みのチャプター数を取得する
     *
     * 公開レッスンを1つも持たないチャプターは、受講済みと判定しない
     */
    private function getCompletedChaptersCount(Attendance $attendance): int
    {
        return $attendance->course->publicChapters
            ->filter(fn (Chapter $chapter) => $chapter->publicLessons->isNotEmpty()
                && $chapter->publicLessons->every(fn (Lesson $lesson) => $attendance->hasCompletedLesson($lesson)))
            ->count();
    }
}
