<?php

namespace App\Http\Controllers\Api\Manager;

use App\Exceptions\ValidationErrorException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\Chapter\BulkDeleteRequest;
use App\Http\Requests\Manager\Chapter\DeleteAllRequest;
use App\Http\Requests\Manager\Chapter\DeleteRequest;
use App\Http\Requests\Manager\Chapter\PatchStatusRequest;
use App\Http\Requests\Manager\Chapter\PutRequest;
use App\Http\Requests\Manager\Chapter\PutStatusRequest;
use App\Http\Requests\Manager\Chapter\ShowRequest;
use App\Http\Requests\Manager\Chapter\SortRequest;
use App\Http\Requests\Manager\Chapter\StoreRequest;
use App\Http\Requests\Manager\Chapter\UpdateStatusRequest;
use App\Http\Resources\Manager\ChapterShowResource;
use App\Model\Chapter;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\LessonAttendance;
use App\Services\Chapter\BulkDeleteChapterService;
use App\Services\Chapter\CreateChapterService;
use App\Services\Chapter\DeleteAllChaptersService;
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
 * @tags Manager-Chapter
 */
class ChapterController extends Controller
{
    /**
     * チャプターを取得
     */
    public function show(ShowRequest $request): ChapterShowResource
    {
        // ログイン中の講師IDを取得
        $managerId = Auth::guard('instructor')->user()->id;

        // 配下の講師情報を取得
        $manager = Instructor::with('managings')->find($managerId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        $chapter = Chapter::with(['lessons', 'course'])->findOrFail($request->chapter_id);

        if ((int) $request->course_id !== $chapter->course->id) {
            // 指定した講座IDがチャプターの講座IDと一致しない場合はエラー応答
            throw new AuthorizationException('Forbidden, invalid course_id.');
        }

        if (! in_array($chapter->course->instructor_id, $instructorIds, true)) {
            // 自身もしくは配下の講師が作成した講座でない場合、権限エラーを返す
            throw new AuthorizationException('Forbidden, invalid instructor_id.');
        }

        return new ChapterShowResource($chapter);
    }

    /**
     * チャプター新規作成API
     */
    public function store(StoreRequest $request, CreateChapterService $createChapterService): JsonResponse
    {
        // ログイン中の講師IDを取得
        $managerId = Auth::guard('instructor')->user()->id;

        /** @var Instructor $manager */
        $manager = Instructor::with('managings')->find($managerId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        /** @var Course $course */
        $course = Course::FindOrFail($request->course_id);

        if (! in_array($course->instructor_id, $instructorIds, true)) {
            // 自分、または配下の講師の講座でなければエラー応答
            throw new AuthorizationException('Forbidden, not allowed to create new chapter.');
        }

        try {
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
    public function put(PutRequest $request, UpdateChapterService $updateChapterService): JsonResponse
    {
        // チャプターを取得
        $chapter = Chapter::with('course')->findOrFail($request->chapter_id);

        // Policy による認可処理に置き換え
        $this->authorize('update', $chapter);

        if ((int) $request->course_id !== $chapter->course->id) {
            // 指定した講座IDがチャプターの講座IDと一致しない場合は更新を許可しない
            throw new AuthorizationException('Forbidden, invalid course_id.');
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
     * チャプター削除API
     */
    public function delete(DeleteRequest $request): JsonResponse
    {
        // ログイン中の講師IDを取得
        $managerId = Auth::guard('instructor')->user()->id;

        // マネージャーが管理する講師を取得
        $manager = Instructor::with('managings')->find($managerId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        // チャプターを取得
        $chapter = Chapter::with(['course', 'lessons'])->findOrFail($request->chapter_id);

        // チャプターに紐づく全レッスンIDを取得
        $lessonIds = $chapter->lessons->pluck('id')->toArray();

        if (! in_array($chapter->course->instructor_id, $instructorIds, true)) {
            // 自分、または配下の講師の講座のチャプターでなければエラー応答
            throw new AuthorizationException('Forbidden, not allowed to delete this chapter.');
        }

        if ((int) $request->course_id !== $chapter->course->id) {
            // 指定した講座に属するチャプターでなければエラー応答
            throw new AuthorizationException('Forbidden, invalid course_id.');
        }

        if (
            LessonAttendance::whereIn('lesson_id', $lessonIds)
            ->exists()
        ) {
            // 指定したチャプター内に受講中のレッスンがあればエラー応答
            throw new AuthorizationException('Forbidden, this lesson has attendance.');
        }

        $chapter->delete();

        return response()->json([
            'result' => true,
        ]);
    }

    /**
     * 複数のチャプター削除API
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
        // リクエストから講座IDを取得
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

            $service(
                courseId: $courseId
            );

            DB::commit();

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

        $courseId = $request->input('course_id');
        $chapterIds = $request->input('chapters');

        $chapters = Chapter::with('course')->whereIn('id', $chapterIds)->get();

        // 全チャプターに対して認可をチェック
        foreach ($chapters as $chapter) {
            $this->authorize('update', $chapter);
        }

        DB::beginTransaction();
        try {
            $service($chapters, $courseId);

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
     * チャプターの公開状態を更新するAPI
     */
    public function updateStatus(UpdateStatusRequest $request): JsonResponse
    {
        $chapter = Chapter::with('course')->findOrFail($request->chapter_id);

        // Policyによる認可処理
        $this->authorize('update', $chapter);

        // チャプターのステータスを更新
        $chapter->update([
            'status' => $request->status,
        ]);

        return response()->json([
            'result' => true,
        ]);
    }

    /**
     * チャプター一括更新API(公開・非公開切り替え)
     */
    public function putStatus(PutStatusRequest $request, UpdateAllChaptersStatusService $service): JsonResponse
    {
        // 任意のチャプター1件を取得（認可チェック用）
        $chapter = Chapter::with('course')
            ->where('course_id', $request->course_id)
            ->firstOrFail();

        // Policyで認可チェック（コースに対する間接認可）
        $this->authorize('update', $chapter);

        $service(
            courseId: $request->course_id,
            status: $request->status
        );

        return response()->json([
            'result' => true,
        ]);
    }

    /**
     * 選択済みチャプターを公開/非公開にするAPI
     */
    public function patchStatus(PatchStatusRequest $request, UpdateChapterStatusService $updateChapterStatusService): JsonResponse
    {
        $chapterIds = $request->input('chapters');
        $chapters = Chapter::with('course')->whereIn('id', $chapterIds)->get();

        // 各チャプターに対してPolicyで認可チェック
        foreach ($chapters as $chapter) {
            $this->authorize('update', $chapter);
        }

        $updateChapterStatusService(
            chapterIds: $chapters->pluck('id'),
            status: $request->status
        );

        return response()->json([
            'result' => true,
        ]);
    }
}
