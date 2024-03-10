<?php

namespace App\Http\Controllers\Api\Manager;

use Exception;
use App\Model\Course;
use App\Model\Chapter;
use App\Model\Instructor;
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
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ChapterController extends Controller
{
    /**
     * チャプター新規作成API
     *
     * @param ChapterStoreRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(ChapterStoreRequest $request)
    {
        try {
            $instructorId = Auth::guard('instructor')->user()->id;
            // 配下の講師情報を取得
            $manager = Instructor::with('managings')->find($instructorId);
            $instructorIds = $manager->managings->pluck('id')->toArray();
            $instructorIds[] = $manager->id;

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
     * チャプター詳細情報を取得
     *
     * @param ChapterShowRequest $request
     * @return ChapterShowResource|\Illuminate\Http\JsonResponse
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
                'message' => "Forbidden, not allowed to edit this course.",
            ], 403);
        }

        return new ChapterShowResource($chapter);
    }

    /**
     * チャプター更新API
     *
     * @param ChapterPatchRequest $request
     * @return \Illuminate\Http\JsonResponse
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
                'message' => "Forbidden, not allowed to edit this chapter.",
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
     * @return \Illuminate\Http\JsonResponse
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
     * チャプター並び替えAPI
     *
     * @param ChapterSortRequest $request
     * @return \Illuminate\Http\JsonResponse
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
                    'message' => "Forbidden, not allowed to edit this course.",
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
     * @return \Illuminate\Http\JsonResponse
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function putStatus(ChapterPutStatusRequest $request)
    {
        $instructorId = Auth::guard('instructor')->user()->id;
        $manager = Instructor::with('managings')->find($instructorId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        Chapter::chapterupdate($request);

        return response()->json([
            'result' => true,
        ]);
    }
}
