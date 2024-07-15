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
use App\Http\Requests\Manager\ChapterShowRequest;
use App\Http\Requests\Manager\ChapterSortRequest;
use App\Http\Requests\Manager\ChapterPatchRequest;
use App\Http\Requests\Manager\ChapterStoreRequest;
use App\Http\Requests\Manager\ChapterDeleteRequest;
use App\Http\Resources\Manager\ChapterShowResource;
use App\Http\Requests\Manager\ChapterPutStatusRequest;
use App\Http\Requests\Manager\ChapterPatchStatusRequest;
use App\Http\Requests\Manager\BulkPatchStatusRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ChapterController extends Controller
{
    /**
     * チャプターを取得
     *
     * @param ChapterShowRequest $request
     * @return ChapterShowResource|JsonResponse
     */
    public function show(ChapterShowRequest $request)
    {
        // ユーザーID取得
        $userId = $request->user()->id;

        // ユーザーIDから配下のinstructorを取得
        $manager = Instructor::with('managings')->find($userId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        // chapter_idから属するlassons含めてデータ取得
        $chapter = Chapter::with(['lessons','course'])->findOrFail($request->chapter_id);

        // 自身もしくは配下のinstructorでない場合はエラー応答
        if (!in_array($chapter->course->instructor_id, $instructorIds, true)) {
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
        try {
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
        // 現在のユーザーを取得
        $instructorId = Auth::guard('instructor')->user()->id;

        // マネージャーが管理する講師を取得
        $manager = Instructor::with('managings')->find($instructorId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        // チャプターを取得
        $chapter = Chapter::with('course')->findOrFail($request->chapter_id);

        // マネージャー自身が作成したチャプターか、または配下の講師が作成したチャプターなら更新を許可
        if (!in_array($chapter->course->instructor_id, $instructorIds, true)) {
            // 失敗結果を返す
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

        // 成功結果を返す
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
        $instructorId = Auth::guard('instructor')->user()->id;
        // 配下の講師情報を取得
        $manager = Instructor::with('managings')->find($instructorId);
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
     * 複数のチャプター公開/非公開API
     */
    public function bulkPatchStatus(BulkPatchStatusRequest $request)
    {
        // $chaptersの中に$course_idと一致しないidが含まれている場合はエラーを返す
        foreach ($request->chapters as $chapter_id) {
            $chapter = Chapter::findOrFail($chapter_id);
            if ($chapter->course_id !== intval($request->course_id)) {
                return response()->json([
                    'result' => false,
                ]);
            }
        }

        // 該当するchaptersを取得
        $chapters = Chapter::whereIn('id', $request->chapters);

        // chaptersのstatusを一括で更新
        $chapters->update([
            'status' => $request->status,
        ]);

        return response()->json([
            'result' => true,
        ]);
    }

    /**
     * チャプター並び替えAPI
     *
     * @param ChapterSortRequest $request
     * @return JsonResponse
     */
    public function sort(ChapterSortRequest $request)
    {
        DB::beginTransaction();
        try {
            // 現在のユーザーを取得
            $userId = Auth::guard('instructor')->user()->id;
            $courseId = $request->input('course_id');
            $chapters = $request->input('chapters');
            $course = Course::findOrFail($courseId);

            // マネージャーが管理する講師を取得
            $manager = Instructor::with('managings')->find($userId);
            $instructorIds = $manager->managings->pluck('id')->toArray();
            $instructorIds[] = $manager->id;

            // マネージャー自身または配下の講師が担当する講座なら更新を許可
            if (!in_array($course->instructor_id, $instructorIds, true)) {
                // 失敗結果を返す
                return response()->json([
                    'result'  => false,
                    'message' => "Forbidden, not allowed to this course.",
                ], 403);
            }

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
        // 現在のユーザーを取得（講師の場合）
        $instructorId = Auth::guard('instructor')->user()->id;

        // マネージャーが管理する講師を取得
        $manager = Instructor::with('managings')->find($instructorId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        // 指定されたチャプターを取得
        $chapter = Chapter::with('course')->findOrFail($request->chapter_id);

        // 自分、または配下の講師の講座のチャプターでなければエラー応答
        if (!in_array($chapter->course->instructor_id, $instructorIds, true)) {
            return response()->json([
                'result' => false,
                'message' => 'Unauthorized access to update chapter status.'
            ], 403);
        }

        // リクエストのcourse_idとチャプターのcourse_idが一致するか確認
        if ((int) $request->course_id !== $chapter->course->id) {
            return response()->json([
                'result'  => false,
                'message' => 'Invalid course_id.',
            ], 403);
        }

        // チャプターのstatusをリクエストのstatusで更新
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
        $instructorId = Auth::guard('instructor')->user()->id;

        /** @var Instructor $manager */
        $manager = Instructor::with('managings')->find($instructorId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        // 認証されたマネージャーとマネージャーが管理する講師の講座IDのリストを取得
        $courseIds = Course::whereIn('instructor_id', $instructorIds)->pluck('id')->toArray();

        // 講師が管理している講座でない場合、権限エラーを返す
        if (!in_array($request->course_id, $courseIds)) {
            return response()->json([
                'result' => false,
                "message" => "Not authorized."
            ], 403);
        }

        $course = Course::findOrFail($request->course_id);
        // 認証者と講座の講師が一致しているか確認
        if (Auth::guard('instructor')->user()->id !== $course->instructor_id) {
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
}
