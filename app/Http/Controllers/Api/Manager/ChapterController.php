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
use App\Model\Lesson;
use App\Model\LessonAttendance;
use App\Services\Chapter\BulkDeleteChapterService;
use App\Services\Chapter\UpdateChapterService;
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
     *
     * @return ChapterShowResource|JsonResponse
     */
    public function show(ShowRequest $request)
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
     *
     * @return JsonResponse
     */
    public function store(StoreRequest $request)
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
            $order = $course->chapters->count();
            $newOrder = $order + 1;
            $chapter = Chapter::create([
                'course_id' => $request->course_id,
                'title' => $request->input('title'),
                'order' => $newOrder,
                'status' => Chapter::STATUS_PUBLIC,
            ]);

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
        // ログイン中の講師IDを取得
        $managerId = Auth::guard('instructor')->user()->id;

        // マネージャーが管理する講師を取得
        $manager = Instructor::with('managings')->find($managerId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        // チャプターを取得
        $chapter = Chapter::with('course')->findOrFail($request->chapter_id);

        if (! in_array($chapter->course->instructor_id, $instructorIds, true)) {
            // 自分、または配下の講師の講座のチャプターでなければエラー応答
            throw new AuthorizationException('Forbidden, not allowed to this chapter.');
        }

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
     *
     * @return JsonResponse
     */
    public function delete(DeleteRequest $request)
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
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkDelete(BulkDeleteRequest $request, BulkDeleteChapterService $bulkDeleteChapterService)
    {
        // ログイン中の講師IDを取得
        $managerId = Auth::guard('instructor')->user()->id;

        // マネージャーが管理する講師を取得
        $manager = Instructor::with('managings')->find($managerId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        $chapterIds = $request->input('chapters', []);
        $courseId = $request->input('course_id');

        try {
            $chapters = Chapter::with(['course', 'lessons'])->whereIn('id', $chapterIds)->get();
            $chapters->each(function (Chapter $chapter) use ($instructorIds, $courseId) {
                if (! in_array($chapter->course->instructor_id, $instructorIds, true)) {
                    // 自分、または配下の講師の講座のチャプターでなければエラー応答
                    throw new AuthorizationException('Forbidden, invalid instructor_id.');
                }
                if ((int) $courseId !== $chapter->course_id) {
                    // 指定した講座に属するチャプターでなければエラー応答
                    throw new AuthorizationException('Forbidden, invalid course_id.');
                }
            });

            $lessonIds = $chapters->pluck('lessons.*.id')->flatten();
            if (LessonAttendance::whereIn('lesson_id', $lessonIds)->exists()) {
                // 受講中のレッスンがあれば、エラー応答
                throw new AuthorizationException('Forbidden, this lesson has attendance.');
            }

            $bulkDeleteChapterService($chapterIds);

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
     *
     * @return JsonResponse
     */
    public function deleteAll(DeleteAllRequest $request)
    {
        // ログイン中の講師IDを取得
        $managerId = Auth::guard('instructor')->user()->id;

        // マネージャーが管理する講師を取得
        $manager = Instructor::with('managings')->find($managerId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        DB::beginTransaction();

        try {
            // リクエストから講座IDを取得
            $courseId = $request->input('course_id');

            // チャプターを取得
            $chapters = Chapter::with(['course', 'lessons'])->where('course_id', $courseId)->get();
            $chapters->each(function (Chapter $chapter) use ($instructorIds) {
                // 自分、または配下の講師の講座のチャプターでなければエラー応答
                if (! in_array($chapter->course->instructor_id, $instructorIds, true)) {
                    throw new AuthorizationException('Forbidden, invalid instructor_id.');
                }
            });

            // チャプターに紐づく全レッスンIDを取得
            $lessonIds = $chapters->pluck('lessons')->flatten()->pluck('id')->toArray();
            if (LessonAttendance::whereIn('lesson_id', $lessonIds)->exists()) {
                // 受講中のレッスンがあれば、エラー応答
                throw new AuthorizationException('Forbidden, this lesson has attendance.');
            }

            // 削除するチャプターに紐づくレッスンを削除する
            Lesson::whereIn('id', $lessonIds)->delete();
            // チャプターを削除
            Chapter::where('course_id', $courseId)->delete();

            DB::commit();

            return response()->json([
                'result' => true,
            ]);
        } catch (AuthorizationException $e) {
            DB::rollBack();
            throw $e;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);

            throw $e;
        }
    }

    /**
     * チャプター並び替えAPI
     *
     * @return JsonResponse
     */
    public function sort(SortRequest $request)
    {
        // ログイン中の講師IDを取得
        $managerId = Auth::guard('instructor')->user()->id;
        $courseId = $request->input('course_id');
        $chapters = $request->input('chapters');
        $course = Course::findOrFail($courseId);

        // マネージャーが管理する講師を取得
        $manager = Instructor::with('managings')->find($managerId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        if (! in_array($course->instructor_id, $instructorIds, true)) {
            // 自分、または配下の講師の講座でなければエラー応答
            throw new AuthorizationException('Forbidden, invalid instructor_id.');
        }

        DB::beginTransaction();
        try {
            foreach ($chapters as $chapter) {
                Chapter::where('id', $chapter['chapter_id'])
                    ->where('course_id', $courseId)
                    ->firstOrFail()
                    ->update([
                        'order' => $chapter['order'],
                    ]);
            }

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
     *
     * @return JsonResponse
     */
    public function updateStatus(UpdateStatusRequest $request)
    {
        // ログイン中の講師IDを取得
        $managerId = Auth::guard('instructor')->user()->id;

        // マネージャーが管理する講師を取得
        $manager = Instructor::with('managings')->find($managerId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        // 指定されたチャプターを取得
        $chapter = Chapter::with('course')->findOrFail($request->chapter_id);

        if (! in_array($chapter->course->instructor_id, $instructorIds, true)) {
            // 自分、または配下の講師の講座のチャプターでなければエラー応答
            throw new ValidationErrorException('Unauthorized access to update chapter status.');
        }

        if ((int) $request->course_id !== $chapter->course->id) {
            // 指定した講座に属するチャプターでなければエラー応答
            throw new ValidationErrorException('Forbidden, invalid course_id.');
        }

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
     *
     * @return JsonResponse
     */
    public function putStatus(PutStatusRequest $request)
    {
        // ログイン中の講師IDを取得
        $managerId = Auth::guard('instructor')->user()->id;

        /** @var Instructor $manager */
        $manager = Instructor::with('managings')->find($managerId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        // 認証されたマネージャーとマネージャーが管理する講師の講座IDのリストを取得
        $courseIds = Course::whereIn('instructor_id', $instructorIds)->pluck('id')->toArray();

        if (! in_array($request->course_id, $courseIds)) {
            // 講座IDがマネージャーが管理する講座IDのリストに含まれていない場合はエラー応答
            throw new ValidationErrorException('Not authorized.');
        }

        $course = Course::findOrFail($request->course_id);
        if (Auth::guard('instructor')->user()->id !== $course->instructor_id) {
            // ログイン中の講師IDが講座の講師IDと一致しない場合はエラー応答
            throw new ValidationErrorException('Not authorized.');
        }
        Chapter::chapterUpdateAll($request->course_id, $request->status);

        return response()->json([
            'result' => true,
        ]);
    }

    /**
     * 選択済みチャプターを公開/非公開にするAPI
     */
    public function patchStatus(PatchStatusRequest $request): JsonResponse
    {
        // ログイン中の講師IDを取得
        $managerId = Auth::guard('instructor')->user()->id;

        // 配下の講師情報を取得
        /** @var Instructor $manager */
        $manager = Instructor::with('managings')->find($managerId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        // リクエストから必要なデータを取得
        $chapterIds = $request->input('chapters');
        $courseId = $request->input('course_id');
        $status = $request->input('status');

        // チャプターデータの取得
        $chapters = Chapter::with('course')->whereIn('id', $chapterIds)->get();

        try {
            $chapters->each(function (Chapter $chapter) use ($instructorIds, $courseId) {
                // 講座に紐づく講師でない場合は許可しない
                if (! in_array($chapter->course->instructor_id, $instructorIds, true)) {
                    throw new AuthorizationException('Forbidden, invalid instructor_id.');
                }

                // 指定した講座IDがチャプターの講座IDと一致しない場合は許可しない
                if ((int) $courseId !== $chapter->course->id) {
                    throw new AuthorizationException('Forbidden, invalid course_id.');
                }
            });

            // チャプターのステータスを一括更新
            Chapter::whereIn('id', $chapterIds)->update(['status' => $status]);

            return response()->json([
                'result' => true,
            ]);
        } catch (AuthorizationException $e) {

            throw $e;
        }
    }
}
