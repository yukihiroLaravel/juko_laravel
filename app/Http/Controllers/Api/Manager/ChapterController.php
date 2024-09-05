<?php

namespace App\Http\Controllers\Api\Manager;

use Exception;
use App\Model\Course;
use App\Model\Chapter;
use App\Model\Instructor;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\Chapter\QueryService;
use App\Exceptions\ValidationErrorException;
use App\Http\Requests\Manager\ChapterShowRequest;
use App\Http\Requests\Manager\ChapterSortRequest;
use App\Http\Requests\Manager\ChapterPatchRequest;
use App\Http\Requests\Manager\ChapterStoreRequest;
use Illuminate\Auth\Access\AuthorizationException;
use App\Http\Requests\Manager\ChapterDeleteRequest;
use App\Http\Resources\Manager\ChapterShowResource;
use App\Http\Requests\Manager\ChapterPutStatusRequest;
use App\Http\Requests\Manager\ChapterBulkDeleteRequest;
use App\Http\Requests\Manager\ChapterPatchStatusRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Requests\Manager\ChaptersPatchStatusRequest;

class ChapterController extends Controller
{
    /**
     * チャプターを取得
     *
     * @return ChapterShowResource|JsonResponse
     */
    public function show(ChapterShowRequest $request, QueryService $queryService)
    {
        // ログイン中の講師IDを取得
        $managerId = Auth::guard('instructor')->user()->id;

        // 配下の講師情報を取得
        $manager = Instructor::with('managings')->find($managerId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        $chapter = $queryService->getChapter($request->chapter_id);

        if (!in_array($chapter->course->instructor_id, $instructorIds, true)) {
            // 自身もしくは配下の講師が作成した講座でない場合、権限エラーを返す
            return response()->json([
                'result' => false,
                'message' => "Forbidden, not allowed to this course.",
            ], 403);
        }

        return new ChapterShowResource($chapter);
    }

    /**
     * チャプター新規作成API
     *
     * @param ChapterStoreRequest $request
     * @return JsonResponse
     */
    public function store(ChapterStoreRequest $request)
    {
        // ログイン中の講師IDを取得
        $managerId = Auth::guard('instructor')->user()->id;

        /** @var Instructor $manager */
        $manager = Instructor::with('managings')->find($managerId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        /** @var Course $course */
        $course = Course::FindOrFail($request->course_id);

        if (!in_array($course->instructor_id, $instructorIds, true)) {
            // 自分、または配下の講師の講座でなければエラー応答
            return response()->json([
                'result'  => false,
                'message' => "Forbidden, not allowed to create new chapter.",
            ], 403);
        }

        try {

            $order =  $course->chapters->count();
            $newOrder = $order + 1;
            Chapter::create([
                'course_id' => $request->course_id,
                'title' => $request->input('title'),
                'order' => $newOrder,
                'status' => Chapter::STATUS_PUBLIC,
            ]);

            return response()->json([
                'result' => true,
            ]);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'result' => false
            ], 500);
        }
    }

    /**
     * チャプター更新API
     *
     * @param ChapterPatchRequest $request
     * @return JsonResponse
     */
    public function update(ChapterPatchRequest $request)
    {
        // ログイン中の講師IDを取得
        $managerId = Auth::guard('instructor')->user()->id;

        // マネージャーが管理する講師を取得
        $manager = Instructor::with('managings')->find($managerId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        // チャプターを取得
        $chapter = Chapter::with('course')->findOrFail($request->chapter_id);

        if (!in_array($chapter->course->instructor_id, $instructorIds, true)) {
            // 自分、または配下の講師の講座のチャプターでなければエラー応答
            return response()->json([
                'result'  => false,
                'message' => "Forbidden, not allowed to this chapter.",
            ], 403);
        }

        if ((int) $request->course_id !== $chapter->course->id) {
            // 指定した講座IDがチャプターの講座IDと一致しない場合は更新を許可しない
            return response()->json([
                'result'  => false,
                'message' => 'Invalid course_id.',
            ], 403);
        }

        // チャプターを更新する
        $chapter->update([
            'title' => $request->title,
        ]);

        return response()->json([
            'result'  => true,
        ]);
    }

    /**
     * チャプター削除API
     *
     * @param ChapterDeleteRequest $request
     * @return JsonResponse
     */
    public function delete(ChapterDeleteRequest $request)
    {
        // ログイン中の講師IDを取得
        $managerId = Auth::guard('instructor')->user()->id;

        // マネージャーが管理する講師を取得
        $manager = Instructor::with('managings')->find($managerId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        $chapter = Chapter::with('course')->findOrFail($request->chapter_id);

        if (!in_array($chapter->course->instructor_id, $instructorIds, true)) {
            // 自分、または配下の講師の講座のチャプターでなければエラー応答
            return response()->json([
                'result'  => false,
                'message' => "Forbidden, not allowed to delete this chapter.",
            ], 403);
        }

        if ((int) $request->course_id !== $chapter->course->id) {
            // 指定した講座に属するチャプターでなければエラー応答
            return response()->json([
                'result'  => false,
                'message' => 'Invalid course_id.',
            ], 403);
        }

        $chapter->delete();
        return response()->json([
            "result" => true
        ]);
    }

    /**
     * 複数のチャプター削除API
     *
     * @param ChapterBulkDeleteRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkDelete(ChapterBulkDeleteRequest $request)
    {
        // ログイン中の講師IDを取得
        $managerId = Auth::guard('instructor')->user()->id;

        // マネージャーが管理する講師を取得
        $manager = Instructor::with('managings')->find($managerId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        $chapterIds = $request->input('chapters', []);
        $courseId = $request->input('course_id');

        DB::beginTransaction();
        try {
            $chapters = Chapter::with('course')->whereIn('id', $chapterIds)->get();
            $chapters->each(function (Chapter $chapter) use ($instructorIds, $courseId) {
                if (!in_array($chapter->course->instructor_id, $instructorIds, true)) {
                    // 自分、または配下の講師の講座のチャプターでなければエラー応答
                    throw new ValidationErrorException('Invalid instructor_id.');
                }
                if ((int) $courseId !== $chapter->course_id) {
                    // 指定した講座に属するチャプターでなければエラー応答
                    throw new ValidationErrorException('Invalid course.');
                }
            });

            Chapter::whereIn('id', $chapterIds)->delete();
            DB::commit();
            return response()->json([
                'result' => true,
            ]);
        } catch (ValidationErrorException $e) {
            return response()->json([
                'result' => false,
                'message' => $e->getMessage(),
            ], 403);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);

            return response()->json([
                'result' => false,
                'message' => 'Failed to delete chapters.',
            ], 500);
        }
    }

    /**
     * チャプター並び替えAPI
     *
     * @param ChapterSortRequest $request
     * @return JsonResponse
     */
    public function sort(ChapterSortRequest $request)
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

        if (!in_array($course->instructor_id, $instructorIds, true)) {
            // 自分、または配下の講師の講座でなければエラー応答
            return response()->json([
                'result'  => false,
                'message' => "Forbidden, not allowed to this course.",
            ], 403);
        }

        DB::beginTransaction();
        try {
            foreach ($chapters as $chapter) {
                Chapter::where('id', $chapter['chapter_id'])
                ->where('course_id', $courseId)
                ->firstOrFail()
                ->update([
                    'order' => $chapter['order']
                ]);
            }

            DB::commit();
            return response()->json([
                'result' => true,
            ]);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'result' => false,
                'message' => 'Not found.'
            ], 404);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return response()->json([
                'result' => false,
            ], 500);
        }
    }

    /**
     * チャプターの公開状態を更新するAPI
     *
     * @param ChapterPatchStatusRequest $request
     * @return JsonResponse
     */
    public function updateStatus(ChapterPatchStatusRequest $request)
    {
        // ログイン中の講師IDを取得
        $managerId = Auth::guard('instructor')->user()->id;

        // マネージャーが管理する講師を取得
        $manager = Instructor::with('managings')->find($managerId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        // 指定されたチャプターを取得
        $chapter = Chapter::with('course')->findOrFail($request->chapter_id);

        if (!in_array($chapter->course->instructor_id, $instructorIds, true)) {
            // 自分、または配下の講師の講座のチャプターでなければエラー応答
            return response()->json([
                'result' => false,
                'message' => 'Unauthorized access to update chapter status.'
            ], 403);
        }

        if ((int) $request->course_id !== $chapter->course->id) {
            // 指定した講座に属するチャプターでなければエラー応答
            return response()->json([
                'result'  => false,
                'message' => 'Invalid course_id.',
            ], 403);
        }

        // チャプターのステータスを更新
        $chapter->update([
          'status' => $request->status
        ]);

        return response()->json([
          'result' => true,
        ]);
    }

    /**
     * チャプター一括更新API(公開・非公開切り替え)
     *
     * @param ChapterPutStatusRequest $request
     * @return JsonResponse
     */
    public function putStatus(ChapterPutStatusRequest $request)
    {
        // ログイン中の講師IDを取得
        $managerId = Auth::guard('instructor')->user()->id;

        /** @var Instructor $manager */
        $manager = Instructor::with('managings')->find($managerId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        // 認証されたマネージャーとマネージャーが管理する講師の講座IDのリストを取得
        $courseIds = Course::whereIn('instructor_id', $instructorIds)->pluck('id')->toArray();

        if (!in_array($request->course_id, $courseIds)) {
            // 講座IDがマネージャーが管理する講座IDのリストに含まれていない場合はエラー応答
            return response()->json([
                'result' => false,
                "message" => "Not authorized."
            ], 403);
        }

        $course = Course::findOrFail($request->course_id);
        if (Auth::guard('instructor')->user()->id !== $course->instructor_id) {
            // ログイン中の講師IDが講座の講師IDと一致しない場合はエラー応答
            return response()->json([
                'result' => false,
                "message" => "Not authorized."
            ], 403);
        }
        Chapter::chapterUpdateAll($request->course_id, $request->status);

        return response()->json([
            'result' => true,
        ]);
    }

   /**
     * 選択済みチャプターを公開/非公開にするAPI
     *
     * @param ChaptersPatchStatusRequest $request
     * @return JsonResponse
     */
    public function patchStatus(ChaptersPatchStatusRequest $request): JsonResponse
    {
        // ログイン中の講師IDを取得
        $managerId = Auth::guard('instructor')->user()->id;

        // 配下の講師情報を取得
        /** @var Instructor $manager */
        $manager = Instructor::with('managings')->find($managerId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        // リクエストから必要なデータを取得
        $chapterIds =  $request->input('chapters');
        $courseId = $request->input('course_id');
        $status = $request->input('status');

        // チャプターデータの取得
        $chapters = Chapter::with('course')->whereIn('id', $chapterIds)->get();

        try {
            $chapters->each(function (Chapter $chapter) use ($instructorIds, $courseId) {
                // 講座に紐づく講師でない場合は許可しない
                if (!in_array($chapter->course->instructor_id, $instructorIds, true)) {
                    throw new AuthorizationException('Invalid instructor_id.');
                }

                // 指定した講座IDがチャプターの講座IDと一致しない場合は許可しない
                if ((int)$courseId !== $chapter->course->id) {
                    throw new AuthorizationException('Invalid course_id.');
                }
            });

            // チャプターのステータスを一括更新
            Chapter::whereIn('id', $chapterIds)->update(['status' => $status]);
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
