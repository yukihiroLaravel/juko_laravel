<?php

namespace App\Http\Controllers\Api\Instructor;

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
use App\Model\Course;
use App\Model\Instructor;
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
        $course = Course::findOrFail($request->course_id);

        // Policyパターンによる認可チェック
        $this->authorize('create', [Lesson::class, $course]);

        DB::beginTransaction();
        try {
            $lesson = $service(
                courseId: $request->course_id,
                chapterId: $request->chapter_id,
                title: $request->title,
                status: Lesson::STATUS_PRIVATE
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
        $lesson = Lesson::with('chapter.course')->findOrFail($request->lesson_id);
        assert($lesson instanceof Lesson);

        // Policy による認可チェック
        $this->authorize('update', $lesson);

        if ((int) $request->course_id !== $lesson->chapter->course_id) {
            throw new AuthorizationException('Forbidden, invalid course_id.');
        }

        if ((int) $request->chapter_id !== $lesson->chapter->id) {
            throw new AuthorizationException('Forbidden, invalid chapter_id.');
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
            $lesson = Lesson::with('chapter')->findOrFail($request->lesson_id);

            // ログイン講師のidと削除レッスンの講師IDが一致しないと削除できない
            $this->authorize('delete', $lesson);

            if ((int) $request->chapter_id !== $lesson->chapter->id) {
                // 指定したチャプターIDがレッスンのチャプターIDと一致しない場合は更新を許可しない
                throw new AuthorizationException('Invalid chapter_id.');
            }

            // 指定した講座IDがレッスンの講座IDと一致しない場合は許可しない
            if ((int) $request->course_id !== $lesson->chapter->course_id) {
                throw new AuthorizationException('Invalid course_id.');
            }

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
        $courseId = $request->input('course_id');
        $chapterId = $request->input('chapter_id');
        $lessonIds = $request->input('lessons');

        DB::beginTransaction();
        try {
            // レッスン情報を取得
            $lessons = Lesson::with('chapter.course', 'lessonAttendances')->whereIn('id', $lessonIds)->get();

            // 自身の講座・チャプターに紐づくレッスンでない場合は許可しない
            $this->authorize('bulkDelete', [Lesson::class, $lessons]);

            $lessons->each(function (Lesson $lesson) use ($chapterId, $courseId) {
                // 指定したチャプターIDがレッスンのチャプターIDと一致しない場合は許可しない
                if ((int) $chapterId !== $lesson->chapter_id) {
                    throw new AuthorizationException('Invalid chapter.');
                }
                // 指定した講座IDがレッスンの講座IDと一致しない場合は許可しない
                if ((int) $courseId !== $lesson->chapter->course_id) {
                    throw new AuthorizationException('Invalid course.');
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
    public function updateStatus(UpdateStatusRequest $request, UpdateLessonStatusService $updateLessonStatusService): JsonResponse
    {
        $lesson = Lesson::with('chapter.course')->findOrFail($request->lesson_id);

        // Policy による認可チェック
        $this->authorize('update', $lesson);

        if ((int) $request->course_id !== $lesson->chapter->course->id) {
            // 指定した講座IDがレッスンの講座IDと一致しない場合は更新を許可しない
            throw new AuthorizationException('Invalid course_id.');
        }

        if ((int) $request->chapter_id !== $lesson->chapter->id) {
            // 指定したチャプターIDがレッスンのチャプターIDと一致しない場合は更新を許可しない
            throw new AuthorizationException('Invalid chapter_id.');
        }

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
        $lesson = Lesson::with('chapter.course')->findOrFail($request->lesson_id);

        // Policy による認可チェック
        $this->authorize('update', $lesson);

        if ((int) $request->course_id !== $lesson->chapter->course_id) {
            throw new AuthorizationException('Invalid course_id.');
        }

        if ((int) $request->chapter_id !== $lesson->chapter->id) {
            throw new AuthorizationException('Invalid chapter_id.');
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
     * チャプターに紐づく全レッスンを削除するAPI
     */
    public function deleteAll(DeleteAllRequest $request, DeleteAllLessonsService $service): JsonResponse
    {
        // チャプターを取得（policyによる認可チェックのために、lessonからcourseまでeager load。 例外throwのためにcourseもloadする。）
        $chapter = Chapter::with(['lessons.chapter.course', 'course'])->findOrFail($request->chapter_id);
        $lesson = $chapter->lessons->first();

        // 現在の講師がチャプターの講座の作成者であるか確認
        $this->authorize('delete', $lesson);

        // 指定された course_id がチャプターに関連付けられている course_id と一致するか確認
        if ((int) $request->course_id !== $chapter->course->id) {
            throw new AuthorizationException('Invalid course_id.');
        }

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
        $courseId = $request->input('course_id');
        $chapterId = $request->input('chapter_id');
        $lessonIds = $request->input('lessons');
        $status = $request->input('status');

        // レッスンデータの取得
        $lessons = Lesson::with('chapter.course')->whereIn('id', $lessonIds)->get();

        // Policy による認可チェック
        $this->authorize('bulkUpdate', [Lesson::class, $lessons]);

        try {
            // 認可
            $lessons->each(function (Lesson $lesson) use ($chapterId, $courseId) {
                // 指定した講座IDがレッスンの講座IDと一致しない場合は許可しない
                if ((int) $courseId !== $lesson->chapter->course_id) {
                    throw new AuthorizationException('Invalid course_id.');
                }
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
