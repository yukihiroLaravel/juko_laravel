<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\Chapter\BulkDeleteRequest;
use App\Http\Requests\Instructor\Chapter\DeleteAllRequest;
use App\Http\Requests\Instructor\Chapter\PatchRequest;
use App\Http\Requests\Instructor\Chapter\PatchStatusRequest;
use App\Http\Requests\Instructor\Chapter\PutStatusRequest;
use App\Http\Requests\Instructor\Chapter\ShowRequest;
use App\Http\Requests\Instructor\Chapter\SortRequest;
use App\Http\Requests\Instructor\Chapter\StoreRequest;
use App\Http\Resources\Instructor\ChapterShowResource;
use App\Model\Chapter;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\LessonAttendance;
use App\Services\Chapter\BulkDeleteChapterService;
use App\Services\Chapter\CreateChapterService;
use App\Services\Chapter\DeleteAllChaptersService;
use App\Services\Chapter\QueryService;
use App\Services\Chapter\SortChaptersService;
use App\Services\Chapter\UpdateAllChaptersStatusService;
use App\Services\Chapter\UpdateChapterService;
use App\Services\Chapter\UpdateChapterStatusService;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @tags Instructor-Chapter
 */
class ChapterController extends Controller
{
    /**
     * チャプター詳細情報を取得
     *
     * @return ChapterShowResource|JsonResponse
     */
    public function show(ShowRequest $request, QueryService $queryService)
    {
        // チャプターを取得
        $chapter = $queryService->getChapter($request->chapter_id);

        if (Auth::guard('instructor')->user()->id !== $chapter->course->instructor_id) {
            // ログインしている講師が作成していないチャプターの更新を許可しない
            throw new AuthorizationException('Invalid instructor_id.');
        }

        if ((int) $request->course_id !== $chapter->course->id) {
            // 指定した講座IDがチャプターの講座IDと一致しない場合は更新を許可しない
            throw new AuthorizationException('Invalid course_id.');
        }

        return new ChapterShowResource($chapter);
    }

    /**
     * チャプター新規作成API
     */
    public function store(StoreRequest $request, CreateChapterService $createChapterService): JsonResponse
    {
        try {
            // 講師の情報を取得
            /** @var Instructor $user */
            $user = Auth::guard('instructor')->user();

            // 講座を取得
            /** @var Course $course */
            $course = Course::with('chapters')->findOrFail($request->input('course_id'));

            if ($course->instructor_id !== $user->id) {
                // 講座の作成者が現在の講師と一致しない場合はエラーを返す
                throw new AuthorizationException('Invalid instructor_id for this course.');
            }

            $chapter = $createChapterService(
                course: $course,
                title: $request->title
            );

            return response()->json([
                'result' => true,
                'chapter_id' => $chapter->id,
            ]);
        } catch (Exception $e) {
            Log::error($e);

            throw $e;
        }
    }

    /**
     * チャプター更新API
     */
    public function put(PatchRequest $request, UpdateChapterService $updateChapterService): JsonResponse
    {
        /** @var Chapter $chapter */
        $chapter = Chapter::findOrFail($request->chapter_id);

        // Policy による認可処理に置き換え
        $this->authorize('update', $chapter);

        if ((int) $request->course_id !== $chapter->course->id) {
            // 指定した講座IDがチャプターの講座IDと一致しない場合は更新を許可しない
            throw new AuthorizationException('Invalid course_id.');
        }

        $updateChapterService(
            chapterId: $request->chapter_id,
            newTitle: $request->title
        );

        return response()->json([
            'result' => true,
        ]);
    }

    /**
     * チャプターの公開/非公開API
     */
    public function patchStatus(PatchStatusRequest $request, UpdateChapterStatusService $updateChapterStatusService): JsonResponse
    {
        $chapters = Chapter::whereIn('id', $request->chapters)->with('course')->get();
        $courseId = $request->course_id;

        $chapters->each(function (Chapter $chapter) use ($courseId) {

            if ((int) $courseId !== $chapter->course->id) {
                throw new AuthorizationException('Forbidden, invalid course_id.');
            }
        });

        $this->authorize('bulkUpdate', [Chapter::class, $chapters]);

        $updateChapterStatusService(
            chapterIds: $chapters->pluck('id'),
            status: $request->status
        );

        return response()->json([
            'result' => true,
        ]);
    }

    /**
     * 選択済チャプターの削除API
     */
    public function bulkDelete(BulkDeleteRequest $request, BulkDeleteChapterService $bulkDeleteChapterService): JsonResponse
    {
        $chapterIds = $request->input('chapters', []);
        $courseId = $request->input('course_id');

        try {
            $chapters = Chapter::with(['course', 'lessons'])->whereIn('id', $chapterIds)->get();

            // 認可処理 policy使用
            $this->authorize('bulkDelete', [Chapter::class, $chapters]);

            $chapters->each(function (Chapter $chapter) use ($courseId) {
                if ((int) $courseId !== $chapter->course_id) {
                    // 指定した講座に属するチャプターでなければエラー応答
                    throw new AuthorizationException('Forbidden, invalid course_id.');
                }
            });

            $bulkDeleteChapterService(
                chapterIds: $chapterIds,
                chapters: $chapters
            );

            return response()->json([
                'result' => true,
            ]);
        } catch (Exception $e) {
            Log::error($e);
            throw $e;
        }
    }

    /**
     * 全チャプター削除API
     */
    public function deleteAll(DeleteAllRequest $request, DeleteAllChaptersService $service): JsonResponse
    {
        $courseId = $request->input('course_id');

        DB::beginTransaction();
        try {
            // 講座に紐づくチャプター情報とレッスン情報を取得
            $course = Course::with(['chapters.lessons', 'chapters.course'])->find($courseId);

            $this->authorize('delete', $course->chapters->first());

            // チャプターに紐づく全レッスンIDを取得
            $lessonIds = $course->chapters->pluck('lessons')->flatten()->pluck('id')->toArray();
            if (LessonAttendance::whereIn('lesson_id', $lessonIds)->exists()) {
                // 受講中のレッスンがあれば、エラー応答
                throw new AuthorizationException('This lesson has attendance.');
            }

            DB::commit();

            $service(
                courseId: $courseId
            );

            return response()->json([
                'result' => true,
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            throw $e;
        }
    }

    /**
     * チャプター並び替えAPI
     */
    public function sort(SortRequest $request, SortChaptersService $service): JsonResponse
    {
        DB::beginTransaction();
        try {
            $courseId = $request->input('course_id');
            $inputChapters = $request->input('chapters');
            $chapterIds = array_column($inputChapters, 'chapter_id');

            $chapters = Chapter::with('course')->whereIn('id', $chapterIds)->get();

            $this->authorize('bulkUpdate', [Chapter::class, $chapters]);

            $service(
                chapters: $inputChapters,
                courseId: $courseId
            );

            DB::commit();

            return response()->json([
                'result' => true,
            ]);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            throw $e;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            throw $e;
        }
    }

    /**
     * チャプター一括更新API
     */
    public function putStatus(PutStatusRequest $request, UpdateAllChaptersStatusService $service): JsonResponse
    {

        $course = Course::findOrFail($request->course_id);

        $chapter = Chapter::with('course')
            ->where('course_id', $course->id)
            ->firstOrFail();

        $this->authorize('update', $chapter);

        $service(
            courseId: $request->course_id,
            status: $request->status
        );

        return response()->json([
            'result' => true,
        ]);
    }
}
