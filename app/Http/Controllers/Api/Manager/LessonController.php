<?php

namespace App\Http\Controllers\Api\Manager;

use App\Exceptions\ValidationErrorException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\Lesson\BulkDeleteRequest;
use App\Http\Requests\Manager\Lesson\DeleteAllRequest;
use App\Http\Requests\Manager\Lesson\DeleteRequest;
use App\Http\Requests\Manager\Lesson\PutRequest;
use App\Http\Requests\Manager\Lesson\PutStatusRequest;
use App\Http\Requests\Manager\Lesson\SortRequest;
use App\Http\Requests\Manager\Lesson\StoreRequest;
use App\Http\Requests\Manager\Lesson\UpdateStatusRequest;
use App\Http\Requests\Manager\Lesson\UpdateTitleRequest;
use App\Model\Attendance;
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
use App\Services\Lesson\UpdateLessonService;
use App\Services\Lesson\UpdateLessonStatusService;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @tags Manager-Lesson
 */
class LessonController extends Controller
{
    /**
     * レッスン新規作成API
     */
    public function store(StoreRequest $request): JsonResponse
    {
        $managerId = Auth::guard('instructor')->user()->id;

        // 配下の講師情報を取得
        /** @var Instructor $manager */
        $manager = Instructor::with('managings')->findOrFail($managerId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        $course = Course::find($request->course_id);

        if (! in_array($course->instructor_id, $instructorIds, true)) {
            // 自分、または配下の講師の講座でなければエラー応答
            throw new AuthorizationException('Forbidden, not allowed to this lesson.');
        }

        $maxOrder = Lesson::where('chapter_id', $request->chapter_id)->max('order');

        DB::beginTransaction();
        try {
            $lesson = Lesson::create([
                'chapter_id' => $request->chapter_id,
                'title' => $request->title,
                'status' => Lesson::STATUS_PRIVATE,
                'order' => (int) $maxOrder + 1,
            ]);
            assert($lesson instanceof Lesson);

            $attendances = Attendance::where('course_id', $request->course_id)->get();
            $lessonId = $lesson->id;
            $attendances->each(function (Attendance $attendance) use ($lessonId) {
                LessonAttendance::create([
                    'attendance_id' => $attendance->id,
                    'lesson_id' => $lessonId,
                    'status' => LessonAttendance::STATUS_BEFORE_ATTENDANCE,
                ]);
            });

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
     * レッスン並び替えAPI
     */
    public function sort(SortRequest $request, SortLessonsService $sortLessonsService): JsonResponse
    {
        DB::beginTransaction();

        try {
            $managerId = Auth::guard('instructor')->user()->id;

            // マネージャーが管理する講師を取得
            $manager = Instructor::with('managings')->find($managerId);

            $instructorIds = $manager->managings->pluck('id')->toArray();
            $instructorIds[] = $manager->id;

            $courseId = $request->input('course_id');
            $chapterId = $request->input('chapter_id');
            $inputLessons = $request->input('lessons');

            // レッスンを一括取得
            $lessons = Lesson::with('chapter.course')
                ->whereIn('id', array_column($inputLessons, 'lesson_id'))
                ->get();

            /// 認可
            $lessons->each(function (Lesson $lesson) use ($instructorIds, $courseId, $chapterId) {
                // 講座に紐づく講師でない場合は許可しない
                if (! in_array($lesson->chapter->course->instructor_id, $instructorIds, true)) {
                    throw new AuthorizationException('Forbidden, not allowed to delete this lesson. Invalid instructor.');
                }
                // 指定した講座IDが1レッスンの講座IDと一致しない場合は許可しない
                if ((int) $courseId !== $lesson->chapter->course->id) {
                    throw new AuthorizationException('Forbidden, not allowed to delete this lesson. Invalid course_id.');
                }
                // 指定したチャプターIDがレッスンのチャプターIDと一致しない場合は許可しない
                if ((int) $chapterId !== $lesson->chapter->id) {
                    throw new AuthorizationException('Forbidden, not allowed to delete this lesson. Invalid chapter_id.');
                }
            });

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
     * レッスンステータス更新API
     */
    public function updateStatus(UpdateStatusRequest $request, UpdateLessonStatusService $updateLessonStatusService): JsonResponse
    {
        $managerId = Auth::guard('instructor')->user()->id;

        /** @var Instructor $manager */
        $manager = Instructor::with('managings')->find($managerId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        // 指定されたレッスンを取得
        $lesson = Lesson::with('chapter.course')->findOrFail($request->lesson_id);

        if (! in_array($lesson->chapter->course->instructor_id, $instructorIds, true)) {
            // 自身もしくは配下の講師の講座でなければエラー応答
            throw new ValidationErrorException('Invalid instructor_id.');
        }

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
    public function updateTitle(UpdateTitleRequest $request): JsonResponse
    {
        // 現在のユーザーを取得（講師の場合）
        $managerId = Auth::guard('instructor')->user()->id;

        // マネージャーが管理する講師を取得
        /** @var Instructor $manager */
        $manager = Instructor::with('managings')->find($managerId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        // 指定されたレッスンを取得
        /** @var Lesson $lesson */
        $lesson = Lesson::with('chapter.course')->findOrFail($request->lesson_id);

        // 自分、または配下の講師の講座のレッスンでなければエラー応答
        if (! in_array($lesson->chapter->course->instructor_id, $instructorIds, true)) {
            throw new ValidationErrorException('Unauthorized access to update lesson title.');
        }

        if ((int) $request->course_id !== $lesson->chapter->course_id) {
            throw new ValidationErrorException('Invalid course_id.');
        }

        if ((int) $request->chapter_id !== $lesson->chapter->id) {
            throw new ValidationErrorException('Invalid chapter_id.');
        }

        $lesson->update([
            'title' => $request->title,
        ]);

        return response()->json([
            'result' => true,
        ]);
    }

    /**
     * 選択済みレッスンステータス一括更新API
     */
    public function putStatus(PutStatusRequest $request, BulkUpdateLessonStatusService $service): JsonResponse
    {
        // ログイン中の講師IDを取得
        $managerId = Auth::guard('instructor')->user()->id;

        // 配下の講師情報を取得
        /** @var Instructor $manager */
        $manager = Instructor::with('managings')->find($managerId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        // リクエストからデータを取得
        $lessonIds = $request->input('lessons');
        $chapterId = $request->input('chapter_id');
        $courseId = $request->input('course_id');
        $status = $request->input('status');

        //レッスンデータの取得
        $lessons = Lesson::with('chapter.course')->whereIn('id', $lessonIds)->get();
        try {
            $lessons->each(function (Lesson $lesson) use ($instructorIds, $chapterId, $courseId) {
                if (! in_array($lesson->chapter->course->instructor_id, $instructorIds, true)) {
                    //講座に紐づく講師でない場合は許可しない
                    throw new AuthorizationException('Invalid instructor_id.');
                }
                if ((int) $courseId !== $lesson->chapter->course->id) {
                    //指定した講座IDがレッスンの講座IDと一致しない場合は許可しない
                    throw new AuthorizationException('Invalid course_id.');
                }
                if ((int) $chapterId !== $lesson->chapter_id) {
                    //指定したチャプターIDがレッスンのチャプターIDと一致しない場合は許可しない
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

    /**
     * チャプターに紐づく全レッスンを削除するAPI
     */
    public function deleteAll(DeleteAllRequest $request, DeleteAllLessonsService $service): JsonResponse
    {
        // チャプターを取得（policyによる認可チェックのために、lessonからcourseまでeager load。 例外throwのためにcourseもloadする。）
        $chapter = Chapter::with(['lessons.chapter.course', 'course'])->findOrFail($request->chapter_id);
        $lesson = $chapter->lessons->first();

        // 現在のマネージャーor配下の講師がチャプターの講座の作成者であるか確認
        $this->authorize('delete', $lesson);

        if ((int) $request->course_id !== $chapter->course->id) {
            // 指定された講座がチャプターに関連付けられている講座と一致しない場合はエラー応答
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
}
