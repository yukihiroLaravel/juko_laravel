<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Enums\Lesson\StatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\Lesson\BulkDeleteRequest;
use App\Http\Requests\Instructor\Lesson\DeleteAllRequest;
use App\Http\Requests\Instructor\Lesson\DeleteRequest;
use App\Http\Requests\Instructor\Lesson\PutRequest;
use App\Http\Requests\Instructor\Lesson\PutStatusRequest;
use App\Http\Requests\Instructor\Lesson\SortRequest;
use App\Http\Requests\Instructor\Lesson\StoreRequest;
use App\Http\Requests\Instructor\Lesson\UpdateStatusRequest;
use App\Http\Requests\Instructor\Lesson\UpdateTitleRequest;
use App\Model\Chapter;
use App\Model\Lesson;
use App\Model\LessonAttendance;
use App\Services\Lesson\BulkDeleteLessonsService;
use App\Services\Lesson\BulkUpdateLessonStatusService;
use App\Services\Lesson\DeleteAllLessonsService;
use App\Services\Lesson\DeleteLessonService;
use App\Services\Lesson\SortLessonsService;
use App\Services\Lesson\StoreLessonService;
use App\Services\Lesson\UpdateLessonService;
use App\Services\Lesson\UpdateLessonStatusService;
use App\Services\Lesson\UpdateLessonTitleService;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @tags Instructor-Lesson
 */
class LessonController extends Controller
{
    /**
     * レッスン新規作成API
     */
    public function store(StoreRequest $request, StoreLessonService $service): JsonResponse
    {
        $chapter = Chapter::with('course')->findOrFail($request->chapter_id);

        // Policyパターンによる認可チェック
        $this->authorize('create', [Lesson::class, $chapter->course]);

        DB::beginTransaction();
        try {
            $lesson = $service(
                chapterId: $chapter->id,
                title: $request->title,
            );

            DB::commit();

            return response()->json([
                'result' => true,
                'lesson_id' => $lesson->id,
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            throw $e;
        }
    }

    /**
     * レッスン更新API
     */
    public function put(PutRequest $request, UpdateLessonService $service): JsonResponse
    {
        // レッスンの取得
        $lesson = Lesson::with(['chapter.course'])->findOrFail($request->lesson_id);
        // Policy による認可チェック
        $this->authorize('update', $lesson);
        // ステータスの特定　??= 代入演算子（左がnullなら右を代入する）
        $status = StatusEnum::from($request->status ?? $lesson->status);
        //  サービス層の呼び出し
        $service(
            $lesson,
            $request['title'],
            $request['url'],
            $request['remarks'] ?? null,
            $status
        );

        return response()->json([
            'result' => true,
        ]);
    }

    /**
     * レッスン削除API
     */
    public function delete(DeleteRequest $request, DeleteLessonService $deleteLessonService): JsonResponse
    {
        DB::beginTransaction();
        try {
            $lesson = Lesson::with('chapter')->findOrFail($request->lesson_id);

            // ログイン講師のidと削除レッスンの講師IDが一致しないと削除できない
            $this->authorize('delete', $lesson);

            // 受講情報が登録されている場合は削除を許可しない
            if (LessonAttendance::where('lesson_id', $lesson->id)->exists()) {
                throw new AuthorizationException('Forbidden, this lesson has attendance.');
            }

            $deleteLessonService($lesson);

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
     * 複数のレッスン削除API
     */
    public function bulkDelete(BulkDeleteRequest $request, BulkDeleteLessonsService $service): JsonResponse
    {
        // リクエストからデータを取得
        $chapterId = $request->input('chapter_id');
        $lessonIds = $request->input('lessons');

        DB::beginTransaction();
        try {
            // レッスン情報を取得
            $lessons = Lesson::with('chapter.course', 'lessonAttendances')->whereIn('id', $lessonIds)->get();

            // 自身の講座・チャプターに紐づくレッスンでない場合は許可しない
            $this->authorize('bulkDelete', [Lesson::class, $lessons]);

            $lessons->each(function (Lesson $lesson) use ($chapterId) {
                // 指定したチャプターIDがレッスンのチャプターIDと一致しない場合は許可しない
                if ((int) $chapterId !== $lesson->chapter_id) {
                    throw new AuthorizationException('Invalid chapter.');
                }
                // 受講情報が登録されている場合は許可しない
                if ($lesson->lessonAttendances->isNotEmpty()) {
                    throw new AuthorizationException('This lesson has attendance.');
                }
            });

            // サービスクラスで対象レッスンの削除処理を実行
            $service(
                lessonIds: $lessons->pluck('id')->toArray(),
                chapterId: $chapterId
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
     * レッスンステータス更新API
     */
    /**
     * ステータス更新処理
     */
    public function updateStatus(UpdateStatusRequest $request, UpdateLessonStatusService $service): JsonResponse
    {
        // レッスンを取得
        $lesson = Lesson::findOrFail($request->lesson_id);

        // Policy による認可チェック
        $this->authorize('update', $lesson);

        //  バリデーション済みのデータを取得
        $validated = $request->validated();

        // 文字列をEnum型に変換
        $status = \App\Enums\Lesson\StatusEnum::from($validated['status']);

        //  変換したEnumの値（文字列）を渡してサービスを実行
        $service($lesson, $status->value);

        // 3レスポンスを返す
        return response()->json([
            'message' => 'ステータスを更新しました。',
        ]);
    }

    /* レッスンタイトル変更API
    */
    public function updateTitle(UpdateTitleRequest $request, UpdateLessonTitleService $service): JsonResponse
    {
        $lesson = Lesson::with('chapter.course')->findOrFail($request->lesson_id);

        // Policy による認可チェック
        $this->authorize('update', $lesson);

        $service(
            lesson: $lesson,
            title: $request->title,
        );

        return response()->json([
            'result' => true,
        ]);
    }

    /**
     * チャプターに紐づく全レッスンを削除するAPI
     */
    public function deleteAll(DeleteAllRequest $request, DeleteAllLessonsService $service): JsonResponse
    {
        // チャプターを取得（policyによる認可チェックのために、lessonからcourseまでeager load。 例外throwのためにcourseもloadする。）
        $chapter = Chapter::with(['lessons.chapter.course', 'course'])->findOrFail($request->chapter_id);
        $lesson = $chapter->lessons->first();

        // 現在の講師がチャプターの講座の作成者であるか確認
        $this->authorize('delete', $lesson);

        DB::beginTransaction();

        try {
            // サービスクラスで削除処理を実行
            $service($chapter->lessons);

            DB::commit();

            return response()->json(['result' => true]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            throw $e;
        }
    }

    /**
     * レッスン並び替えAPI
     */
    public function sort(SortRequest $request, SortLessonsService $sortLessonsService): JsonResponse
    {
        DB::beginTransaction();

        try {
            $inputLessons = $request->input('lessons');

            // レッスン一括取得
            $lessons = Lesson::with('chapter.course')->whereIn('id', $inputLessons)->get();

            // Policy による認可チェック
            $this->authorize('bulkUpdate', [Lesson::class, $lessons]);

            $sortLessonsService($lessons, $inputLessons);

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
     * 選択済みのレッスンステータス一括更新API
     */
    public function putStatus(PutStatusRequest $request, BulkUpdateLessonStatusService $service): JsonResponse
    {
        // リクエストからデータを取得
        $chapterId = $request->input('chapter_id');
        $lessonIds = $request->input('lessons');
        $status = $request->input('status');

        // レッスンデータの取得
        $lessons = Lesson::with('chapter.course')->whereIn('id', $lessonIds)->get();

        // Policy による認可チェック
        $this->authorize('bulkUpdate', [Lesson::class, $lessons]);

        try {
            // 認可
            $lessons->each(function (Lesson $lesson) use ($chapterId) {
                // 指定したチャプターIDがレッスンのチャプターIDと一致しない場合は許可しない
                if ((int) $chapterId !== $lesson->chapter_id) {
                    throw new AuthorizationException('Invalid chapter_id.');
                }
            });

            $service(
                lessons: $lessons,
                status: $status
            );

            return response()->json([
                'result' => true,
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'result' => false,
                'message' => $e->getMessage(),
            ], 403);
        }
    }
}
