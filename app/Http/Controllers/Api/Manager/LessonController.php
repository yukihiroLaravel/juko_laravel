<?php

namespace App\Http\Controllers\Api\Manager;

use App\Exceptions\ValidationErrorException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\Lesson\BulkDeleteRequest;
use App\Http\Requests\Manager\Lesson\DeleteRequest;
use App\Http\Requests\Manager\Lesson\PutRequest;
use App\Http\Requests\Manager\Lesson\PutStatusRequest;
use App\Http\Requests\Manager\Lesson\UpdateStatusRequest;
use App\Http\Requests\Manager\Lesson\UpdateTitleRequest;
use App\Model\Chapter;
use App\Model\Lesson;
use App\Model\LessonAttendance;
use App\Services\Lesson\BulkDeleteLessonsService;
use App\Services\Lesson\BulkUpdateLessonStatusService;
use App\Services\Lesson\DeleteLessonService;
use App\Services\Lesson\UpdateLessonService;
use App\Services\Lesson\UpdateLessonStatusService;
use App\Services\Lesson\UpdateLessonTitleService;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @tags Manager-Lesson
 */
class LessonController extends Controller
{
    /**
     * レッスン更新API
     */
    public function put(PutRequest $request, UpdateLessonService $service): JsonResponse
    {
        $lesson = Lesson::with('chapter.course')->findOrFail($request->lesson_id);
        assert($lesson instanceof Lesson);

        // Policy による認可チェック
        $this->authorize('update', $lesson);

        if ((int) $request->course_id !== $lesson->chapter->course_id) {
            // 講座IDが不正な場合は403エラー
            throw new AuthorizationException('Invalid course_id.');
        }

        if ((int) $request->chapter_id !== $lesson->chapter->id) {
            // チャプターIDが不正な場合は403エラー
            throw new AuthorizationException('Invalid chapter_id.');
        }

        // UpdateLessonServiceを呼び出し更新処理
        $service($lesson, $request->title, $request->url, $request->remarks, $request->status);

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
            // レッスン情報を取得
            /** @var Lesson $lesson */
            $lesson = Lesson::with('chapter')->findOrFail($request->lesson_id);

            // 自分、または配下の講師の講座でないと削除できない
            $this->authorize('delete', $lesson);

            // 指定したチャプターIDがレッスンのチャプターIDと一致しない場合は許可しない
            if ((int) $request->chapter_id !== $lesson->chapter->id) {
                throw new AuthorizationException('Invalid chapter_id.');
            }

            // 指定した講座IDがレッスンの講座IDと一致しない場合は許可しない
            if ((int) $request->course_id !== $lesson->chapter->course_id) {
                throw new AuthorizationException('Invalid course_id.');
            }

            // 受講情報が登録されている場合は許可しない
            if (LessonAttendance::where('lesson_id', $lesson->id)->exists()) {
                throw new AuthorizationException('Forbidden, not allowed to delete this lesson.');
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
     * レッスンステータス更新API
     */
    public function updateStatus(UpdateStatusRequest $request, UpdateLessonStatusService $updateLessonStatusService): JsonResponse
    {
        // 指定されたレッスンを取得
        $lesson = Lesson::with('chapter.course')->findOrFail($request->lesson_id);

        // Policy による認可チェック
        $this->authorize('update', $lesson);

        if ((int) $request->course_id !== $lesson->chapter->course->id) {
            // 指定した講座IDがレッスンの講座IDと一致しない場合は更新を許可しない
            throw new ValidationErrorException('Invalid course_id.');
        }

        if ((int) $request->chapter_id !== $lesson->chapter->id) {
            // 指定したチャプターIDがレッスンのチャプターIDと一致しない場合は更新を許可しない
            throw new ValidationErrorException('Invalid chapter_id.');
        }

        $lesson = Lesson::findOrFail($request->lesson_id);
        // サービスの呼び出し（関数のように使える）
        $updateLessonStatusService($lesson, $request->status);

        return response()->json([
            'result' => true,
        ]);
    }

    /**
     * レッスンタイトル変更API
     */
    public function updateTitle(UpdateTitleRequest $request, UpdateLessonTitleService $service): JsonResponse
    {
        // 指定されたレッスンを取得
        /** @var Lesson $lesson */
        $lesson = Lesson::with('chapter.course')->findOrFail($request->lesson_id);

        // Policy による認可チェック
        $this->authorize('update', $lesson);

        if ((int) $request->course_id !== $lesson->chapter->course_id) {
            throw new ValidationErrorException('Invalid course_id.');
        }

        if ((int) $request->chapter_id !== $lesson->chapter->id) {
            throw new ValidationErrorException('Invalid chapter_id.');
        }

        $service(
            lesson: $lesson,
            title: $request->title,
        );

        return response()->json([
            'result' => true,
        ]);
    }

    /**
     * 選択済みレッスンステータス一括更新API
     */
    public function putStatus(PutStatusRequest $request, BulkUpdateLessonStatusService $service): JsonResponse
    {
        // リクエストからデータを取得
        $lessonIds = $request->input('lessons');
        $chapterId = $request->input('chapter_id');
        $courseId = $request->input('course_id');
        $status = $request->input('status');

        // レッスンデータの取得
        $lessons = Lesson::with('chapter.course')->whereIn('id', $lessonIds)->get();

        // Policy による認可チェック
        $this->authorize('bulkUpdate', [Lesson::class, $lessons]);

        try {
            $lessons->each(function (Lesson $lesson) use ($chapterId, $courseId) {
                if ((int) $courseId !== $lesson->chapter->course->id) {
                    // 指定した講座IDがレッスンの講座IDと一致しない場合は許可しない
                    throw new AuthorizationException('Invalid course_id.');
                }
                if ((int) $chapterId !== $lesson->chapter_id) {
                    // 指定したチャプターIDがレッスンのチャプターIDと一致しない場合は許可しない
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
            // エラーハンドリング、認可に失敗した場合エラーを返す
            return response()->json([
                'result' => false,
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * 選択済みレッスン削除API
     */
    public function bulkDelete(BulkDeleteRequest $request, BulkDeleteLessonsService $service): JsonResponse
    {
        // リクエストからデータを取得
        $lessonIds = $request->input('lessons');
        $chapterId = $request->input('chapter_id');
        $courseId = $request->input('course_id');

        // レッスン情報を取得
        $lessons = Lesson::with('chapter.course', 'lessonAttendances')->whereIn('id', $lessonIds)->get();
        DB::beginTransaction();

        try {
            // 自身もしくは配下の講師の講座・チャプターに紐づくレッスンでない場合は許可しない
            $this->authorize('bulkDelete', [Lesson::class, $lessons]);

            $lessons->each(function (Lesson $lesson) use ($chapterId, $courseId) {
                // 指定した講座IDがレッスンの講座IDと一致しない場合は許可しない
                if ((int) $courseId !== $lesson->chapter->course->id) {
                    throw new AuthorizationException('Invalid course_id.');
                }
                // 指定したチャプターIDがレッスンのチャプターIDと一致しない場合は許可しない
                if ((int) $chapterId !== $lesson->chapter->id) {
                    throw new AuthorizationException('Invalid chapter_id.');
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
}
